<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KycStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
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

    public function pending(Request $request): View
    {
        $this->authorize('kyc.review');

        // The queue defaults to real submissions awaiting a decision
        // (UNDER_REVIEW) — not every freshly registered account, which sits in
        // PENDING until the citizen actually uploads documents. Those accounts
        // still appear in the Users list, so the queue exposes a status filter
        // (with counts) to explain where each one is rather than looking empty
        // while the Users list shows people "waiting".
        $status = $request->input('status');
        $selected = KycStatus::tryFrom((string) $status);

        $query = User::query()->with(['biometrics', 'commune.wilaya']);

        if ($status !== 'all') {
            $selected ??= KycStatus::UNDER_REVIEW;
            $query->where('kyc_status', $selected);
        }

        $users = $query
            ->orderByRaw('kyc_submitted_at IS NULL, kyc_submitted_at ASC')
            ->paginate(20)
            ->withQueryString();

        // One grouped query drives every tab's badge.
        $counts = User::query()
            ->selectRaw('kyc_status, COUNT(*) as aggregate')
            ->groupBy('kyc_status')
            ->pluck('aggregate', 'kyc_status');

        return view('admin.kyc.index', [
            'users' => $users,
            'counts' => $counts,
            'activeStatus' => $status === 'all' ? 'all' : ($selected?->value ?? KycStatus::UNDER_REVIEW->value),
        ]);
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
     * Notify the citizen of a KYC decision over every channel (email, in-app row,
     * push). KycStatusNotification owns the copy and renders it in the citizen's
     * preferred language; delivery failures are logged but never bubble up into a
     * 500 that would undo the admin's decision.
     */
    private function notifyDecision(User $user, string $type, ?string $reason = null): void
    {
        try {
            $user->notify(new KycStatusNotification($type, $reason));
        } catch (\Throwable $e) {
            Log::error('KYC decision notification failed', ['user_id' => $user->id, 'type' => $type, 'error' => $e->getMessage()]);
        }
    }
}
