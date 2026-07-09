<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommercialRegisterStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CommercialRegister;
use App\Models\UserNotification;
use App\Notifications\CommercialRegisterStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminCommercialRegisterController extends Controller
{
    /** Public "type" slug → the column that stores the scan. */
    private const DOCUMENT_FIELDS = [
        'register' => 'register_document_path',
        'tax-card' => 'tax_card_document_path',
    ];

    public function pending(): View
    {
        $this->authorize('commercial-register.review');

        $registers = CommercialRegister::where('status', CommercialRegisterStatus::PENDING)
            ->with('user')
            ->orderBy('submitted_at')
            ->paginate(20);

        return view('admin.commercial-registers.index', compact('registers'));
    }

    public function show(CommercialRegister $commercialRegister): View
    {
        $this->authorize('commercial-register.review');

        $commercialRegister->load('user', 'reviewedBy');

        return view('admin.commercial-registers.show', ['register' => $commercialRegister]);
    }

    /**
     * Stream one of a submission's scans to an authorised admin. Files are on the
     * private disk; this route (behind the admin role middleware) is the only way
     * to view them.
     */
    public function document(CommercialRegister $commercialRegister, string $type): StreamedResponse
    {
        $this->authorize('commercial-register.review');

        abort_unless(array_key_exists($type, self::DOCUMENT_FIELDS), 404);

        $path = $commercialRegister->{self::DOCUMENT_FIELDS[$type]};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function approve(CommercialRegister $commercialRegister): RedirectResponse
    {
        $this->authorize('commercial-register.approve');

        $commercialRegister->update([
            'status' => CommercialRegisterStatus::APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'rejection_reason' => null,
        ]);

        AuditLog::log('COMMERCIAL_REGISTER_APPROVED', 'CommercialRegister', $commercialRegister->id);
        $this->notifyDecision($commercialRegister, 'approved');

        return redirect()->route('admin.commercial-registers.index')
            ->with('success', __('admin.flash.cr_approved'));
    }

    public function reject(Request $request, CommercialRegister $commercialRegister): RedirectResponse
    {
        $this->authorize('commercial-register.reject');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        // REJECTED (not deleted) so the user can fix the issue and resubmit the
        // same record; the reason is stored so they see what to correct.
        $commercialRegister->update([
            'status' => CommercialRegisterStatus::REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'rejection_reason' => $validated['reason'],
        ]);

        AuditLog::log('COMMERCIAL_REGISTER_REJECTED', 'CommercialRegister', $commercialRegister->id, null, null, [
            'reason' => $validated['reason'],
        ]);
        $this->notifyDecision($commercialRegister, 'rejected', $validated['reason']);

        return redirect()->route('admin.commercial-registers.index')
            ->with('success', __('admin.flash.cr_rejected'));
    }

    /**
     * Email + in-app notify the user of a decision. The in-app row is stored in
     * the user's preferred language; mail failures are logged, never fatal.
     */
    private function notifyDecision(CommercialRegister $register, string $type, ?string $reason = null): void
    {
        $user = $register->user;
        if (! $user) {
            return;
        }

        $locale = $user->preferredLocale();
        UserNotification::record(
            $user->id,
            __("commercial-register.notif_{$type}_title", [], $locale),
            __("commercial-register.notif_{$type}_body", ['reason' => $reason ?? ''], $locale),
            route('citizen.commercial-register'),
        );

        try {
            $user->notify(new CommercialRegisterStatusNotification($type, $reason));
        } catch (\Throwable $e) {
            Log::error('Commercial register decision email failed', [
                'user_id' => $user->id, 'type' => $type, 'error' => $e->getMessage(),
            ]);
        }
    }
}
