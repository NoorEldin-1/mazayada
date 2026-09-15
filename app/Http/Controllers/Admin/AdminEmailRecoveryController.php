<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmailRecoveryStatus;
use App\Http\Controllers\Controller;
use App\Models\EmailRecoveryRequest;
use App\Services\EmailRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Review queue for "lost my email" requests. Identity verification work, so it
 * rides on the KYC permissions: kyc.review to see/pick up, kyc.approve and
 * kyc.reject to decide.
 */
class AdminEmailRecoveryController extends Controller
{
    public function __construct(private readonly EmailRecoveryService $recovery) {}

    public function index(Request $request): View
    {
        $this->authorize('kyc.review');

        // Defaults to the requests still awaiting a decision; each status and
        // "all" are one tab away.
        $status = (string) $request->input('status', 'open');
        $selected = EmailRecoveryStatus::tryFrom($status);

        $query = EmailRecoveryRequest::query()->with('user');

        if ($selected) {
            $query->where('status', $selected);
        } elseif ($status !== 'all') {
            $status = 'open';
            $query->whereIn('status', EmailRecoveryStatus::openCases());
        }

        // Open work is served oldest-first (a queue); decided history newest-first.
        $status === 'open' || $selected?->isOpen()
            ? $query->orderBy('submitted_at')
            : $query->latest('submitted_at');

        $requests = $query->paginate(20)->withQueryString();

        $counts = EmailRecoveryRequest::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.email-recovery.index', [
            'requests' => $requests,
            'counts' => $counts,
            'activeStatus' => $status,
        ]);
    }

    public function show(EmailRecoveryRequest $emailRecovery): View
    {
        $this->authorize('kyc.review');

        $emailRecovery->load(['user.biometrics', 'reviewedBy']);

        return view('admin.email-recovery.show', ['request' => $emailRecovery]);
    }

    /** Stream the submitted selfie (private disk) to an authorised reviewer. */
    public function selfie(EmailRecoveryRequest $emailRecovery): StreamedResponse
    {
        $this->authorize('kyc.review');

        $path = $emailRecovery->selfie_with_id_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function startReview(EmailRecoveryRequest $emailRecovery): RedirectResponse
    {
        $this->authorize('kyc.review');

        return $this->act(fn () => $this->recovery->startReview($emailRecovery, auth()->user()),
            __('email_recovery.flash.review_started'));
    }

    public function approve(EmailRecoveryRequest $emailRecovery): RedirectResponse
    {
        $this->authorize('kyc.approve');

        return $this->act(fn () => $this->recovery->approve($emailRecovery, auth()->user()),
            __('email_recovery.flash.approved'), toQueue: true);
    }

    public function reject(Request $request, EmailRecoveryRequest $emailRecovery): RedirectResponse
    {
        $this->authorize('kyc.reject');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        return $this->act(fn () => $this->recovery->reject($emailRecovery, auth()->user(), $validated['reason']),
            __('email_recovery.flash.rejected'), toQueue: true);
    }

    /** Run a state transition and flash its outcome (a stale action is shown, not thrown). */
    private function act(callable $action, string $success, bool $toQueue = false): RedirectResponse
    {
        try {
            $action();
        } catch (RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return ($toQueue ? redirect()->route('admin.email-recovery.index') : back())
            ->with('success', $success);
    }
}
