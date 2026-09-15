<?php

namespace App\Services;

use App\Enums\EmailRecoveryStatus;
use App\Models\AuditLog;
use App\Models\EmailRecoveryRequest;
use App\Models\User;
use App\Notifications\EmailRecoveryStatusNotification;
use App\Services\Api\TokenService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * "Lost my email" recovery (الطلب: فقدت بريدك الإلكتروني). Single source of the
 * rules shared by the web form and POST /api/v1/auth/email-recovery:
 *
 *  1. the citizen proves who they are — NIN + birth date + phone must match the
 *     account — and uploads a selfie holding their ID card;
 *  2. an admin compares the selfie with the KYC documents and approves or
 *     rejects the request;
 *  3. on approval the account email is replaced, every web session and API token
 *     is revoked, and the citizen is told at the NEW address.
 *
 * Throws ValidationException (field-keyed) for anything the citizen can fix and
 * RuntimeException for an admin action that no longer fits the request state.
 */
class EmailRecoveryService
{
    /** Submissions allowed per NIN / per IP within one hour. */
    public const MAX_PER_NIN_PER_HOUR = 3;

    public const MAX_PER_IP_PER_HOUR = 10;

    public function __construct(private readonly TokenService $tokens) {}

    /**
     * Validation rules for a submission (web form + API).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'nin' => ['required', 'digits:18', 'exists:users,nin'],
            'birth_date' => ['required', 'date_format:Y-m-d'],
            'phone' => ['required', 'regex:/^0\d{9}$/'],
            'new_email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'selfie_with_id' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png', 'max:1024'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'nin.exists' => __('email_recovery.errors.nin_not_found'),
            'phone.regex' => __('email_recovery.errors.phone_format'),
            'new_email.unique' => __('email_recovery.errors.email_taken'),
        ];
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        return [
            'nin' => __('email_recovery.form.nin'),
            'birth_date' => __('email_recovery.form.birth_date'),
            'phone' => __('email_recovery.form.phone'),
            'new_email' => __('email_recovery.form.new_email'),
            'selfie_with_id' => __('email_recovery.form.selfie'),
        ];
    }

    /**
     * Record a new request after the identity data has been matched to the
     * account. $data is already validated against rules().
     *
     * @param  array{nin: string, birth_date: string, phone: string, new_email: string}  $data
     */
    public function submit(array $data, UploadedFile $selfie, ?string $ip = null): EmailRecoveryRequest
    {
        $user = User::where('nin', $data['nin'])->first();

        // Staff accounts are managed by the platform, never self-recovered.
        if (! $user || $user->isStaff()) {
            throw ValidationException::withMessages(['nin' => __('email_recovery.errors.not_available')]);
        }

        $errors = [];
        if ($user->birth_date?->toDateString() !== $data['birth_date']) {
            $errors['birth_date'] = __('email_recovery.errors.birth_date_mismatch');
        }
        if ($this->normalizePhone((string) $user->phone) !== $this->normalizePhone($data['phone'])) {
            $errors['phone'] = __('email_recovery.errors.phone_mismatch');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($this->openRequestFor($user)) {
            throw ValidationException::withMessages(['nin' => __('email_recovery.errors.already_pending')]);
        }

        // Private disk — the selfie is identity data (Law 18-07) and is only ever
        // streamed to reviewers through the gated admin route.
        $path = $selfie->store('email-recovery/'.$user->id, 'local');

        $request = EmailRecoveryRequest::create([
            'user_id' => $user->id,
            'nin' => $data['nin'],
            'birth_date' => $data['birth_date'],
            'phone' => $data['phone'],
            'old_email' => $user->email,
            'new_email' => mb_strtolower(trim($data['new_email'])),
            'selfie_with_id_path' => $path,
            'status' => EmailRecoveryStatus::PENDING,
            'submitted_at' => now(),
            'ip_address' => $ip,
        ]);

        AuditLog::log('EMAIL_RECOVERY_SUBMITTED', 'User', $user->id, $user->id, $user->role?->value, [
            'request_id' => $request->id,
        ], $ip);

        return $request;
    }

    /** The most recent request filed for a NIN, or null when there is none. */
    public function latestForNin(string $nin): ?EmailRecoveryRequest
    {
        return EmailRecoveryRequest::where('nin', $nin)->latest('created_at')->first();
    }

    public function openRequestFor(User $user): ?EmailRecoveryRequest
    {
        return EmailRecoveryRequest::where('user_id', $user->id)
            ->whereIn('status', EmailRecoveryStatus::openCases())
            ->first();
    }

    /** A reviewer picks the request up: PENDING → UNDER_REVIEW. */
    public function startReview(EmailRecoveryRequest $request, User $reviewer): void
    {
        if ($request->status !== EmailRecoveryStatus::PENDING) {
            throw new RuntimeException(__('email_recovery.flash.invalid_state'));
        }

        $request->update([
            'status' => EmailRecoveryStatus::UNDER_REVIEW,
            'review_started_at' => now(),
            'reviewed_by' => $reviewer->id,
        ]);

        AuditLog::log('EMAIL_RECOVERY_REVIEW_STARTED', 'User', $request->user_id, $reviewer->id, $reviewer->role?->value, [
            'request_id' => $request->id,
        ]);
    }

    /**
     * Approve: replace the account email, revoke every session and token, and
     * notify the citizen at the new address.
     */
    public function approve(EmailRecoveryRequest $request, User $reviewer): void
    {
        if (! $request->isOpen()) {
            throw new RuntimeException(__('email_recovery.flash.invalid_state'));
        }

        $user = $request->user;
        if (! $user) {
            throw new RuntimeException(__('email_recovery.flash.invalid_state'));
        }

        // The address was free at submission time; another account may have
        // claimed it while the request waited in the queue.
        $taken = User::where('email', $request->new_email)->where('id', '!=', $user->id)->exists();
        if ($taken) {
            throw new RuntimeException(__('email_recovery.errors.email_taken'));
        }

        DB::transaction(function () use ($request, $user, $reviewer) {
            $user->update(['email' => $request->new_email]);

            $request->update([
                'status' => EmailRecoveryStatus::APPROVED,
                'rejection_reason' => null,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
            ]);
        });

        // Whoever holds the lost mailbox may also hold a live session — sign
        // every device out so only the verified owner gets back in.
        invalidate_user_sessions($user->id);
        $this->tokens->revokeAll($user);

        AuditLog::log('EMAIL_RECOVERY_APPROVED', 'User', $user->id, $reviewer->id, $reviewer->role?->value, [
            'request_id' => $request->id,
            'from' => $request->old_email,
            'to' => $request->new_email,
        ]);

        $this->notify($request, 'approved');
    }

    public function reject(EmailRecoveryRequest $request, User $reviewer, string $reason): void
    {
        if (! $request->isOpen()) {
            throw new RuntimeException(__('email_recovery.flash.invalid_state'));
        }

        $request->update([
            'status' => EmailRecoveryStatus::REJECTED,
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer->id,
        ]);

        AuditLog::log('EMAIL_RECOVERY_REJECTED', 'User', $request->user_id, $reviewer->id, $reviewer->role?->value, [
            'request_id' => $request->id,
            'reason' => $reason,
        ]);

        $this->notify($request, 'rejected', $reason);
    }

    /**
     * Tell the citizen about the decision. The account's own email is the one
     * they lost, so: approved → the user (whose email is now the new one) gets
     * mail + in-app + push; rejected → in-app + push on the account, and the mail
     * goes to the address they asked for. Delivery failures never undo a decision.
     */
    private function notify(EmailRecoveryRequest $request, string $type, ?string $reason = null): void
    {
        $user = $request->user;
        if (! $user) {
            return;
        }

        try {
            $user->notify(new EmailRecoveryStatusNotification($type, $reason, $request->new_email));

            if ($type === 'rejected') {
                Notification::route('mail', $request->new_email)->notify(
                    (new EmailRecoveryStatusNotification($type, $reason, $request->new_email, $user->name))
                        ->locale($user->preferredLocale())
                );
            }
        } catch (\Throwable $e) {
            Log::error('Email recovery decision notification failed', [
                'user_id' => $user->id, 'type' => $type, 'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone) ?? '';
    }
}
