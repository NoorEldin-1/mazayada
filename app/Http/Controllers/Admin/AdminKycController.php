<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KycStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\KycStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminKycController extends Controller
{
    /** Map of the document "type" slug to the biometrics column it lives in. */
    private const DOCUMENT_FIELDS = [
        'id-front' => 'id_front_path',
        'id-back' => 'id_back_path',
        'selfie-with-id' => 'selfie_with_id_path',
        'photo-biometric' => 'photo_biometric_path',
    ];

    public function pending(): View
    {
        $this->authorize('kyc.review');

        // Only real submissions awaiting a decision — not every freshly
        // registered (PENDING) account.
        $users = User::where('kyc_status', KycStatus::UNDER_REVIEW)
            ->with(['biometrics', 'commune.wilaya'])
            ->orderBy('kyc_submitted_at')
            ->paginate(20);

        return view('admin.kyc.index', compact('users'));
    }

    public function show(User $user): View
    {
        $this->authorize('kyc.review');

        $user->load('biometrics', 'commune.wilaya');

        return view('admin.kyc.show', compact('user'));
    }

    /**
     * Stream one of a user's KYC documents to an authorised admin. Files are on
     * the private disk; this route (behind the admin role middleware) is the
     * only way to view them.
     */
    public function document(User $user, string $type): StreamedResponse
    {
        $this->authorize('kyc.review');

        abort_unless(array_key_exists($type, self::DOCUMENT_FIELDS), 404);

        $path = $user->biometrics?->{self::DOCUMENT_FIELDS[$type]};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function approve(User $user): RedirectResponse
    {
        $this->authorize('kyc.approve');

        $user->update([
            'kyc_status' => KycStatus::COMPLETE,
            'kyc_completed_at' => now(),
            'kyc_rejection_reason' => null,
        ]);

        // Record who verified the biometrics, when (spec §5.2.2).
        if ($user->biometrics) {
            $user->biometrics->update([
                'kyc_verified_by' => auth()->id(),
                'kyc_verified_at' => now(),
            ]);
        }

        AuditLog::log('KYC_APPROVED', 'User', $user->id);
        $this->notifyDecision($user, 'approved');

        return redirect()->route('admin.kyc.index')->with('success', __('admin.flash.kyc_approved'));
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $this->authorize('kyc.reject');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        // REJECTED (not SUSPENDED) so the citizen can fix the issue and resubmit;
        // the reason is stored on the user so they can see what to correct.
        $user->update([
            'kyc_status' => KycStatus::REJECTED,
            'kyc_rejection_reason' => $validated['reason'],
        ]);

        AuditLog::log('KYC_REJECTED', 'User', $user->id, null, null, [
            'reason' => $validated['reason'],
        ]);
        $this->notifyDecision($user, 'rejected', $validated['reason']);

        return redirect()->route('admin.kyc.index')->with('success', __('admin.flash.kyc_rejected'));
    }

    /**
     * Email + in-app notify the citizen of a KYC decision. The in-app row is
     * stored in the citizen's preferred language; mail failures are logged but
     * never bubble up into a 500.
     */
    private function notifyDecision(User $user, string $type, ?string $reason = null): void
    {
        $locale = $user->preferredLocale();
        UserNotification::record(
            $user->id,
            __("kyc.notif_{$type}_title", [], $locale),
            __("kyc.notif_{$type}_body", ['reason' => $reason ?? ''], $locale),
            route('citizen.kyc'),
        );

        try {
            $user->notify(new KycStatusNotification($type, $reason));
        } catch (\Throwable $e) {
            Log::error('KYC decision email failed', ['user_id' => $user->id, 'type' => $type, 'error' => $e->getMessage()]);
        }
    }
}
