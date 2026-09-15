<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Guest "lost my email" page: the citizen files a recovery request (identity data
 * + selfie holding the ID) and can look up the status of their latest request.
 * All rules live in EmailRecoveryService, shared with the mobile API.
 */
class EmailRecoveryController extends Controller
{
    public function __construct(private readonly EmailRecoveryService $recovery) {}

    public function create(): View
    {
        return view('auth.recover-email');
    }

    public function store(Request $request): RedirectResponse
    {
        // Throttled here rather than with route middleware so the citizen gets the
        // message on the form instead of a bare 429 page.
        $keys = [
            'email-recovery:ip:'.$request->ip() => EmailRecoveryService::MAX_PER_IP_PER_HOUR,
            'email-recovery:nin:'.preg_replace('/\D/', '', (string) $request->input('nin')) => EmailRecoveryService::MAX_PER_NIN_PER_HOUR,
        ];
        foreach ($keys as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return back()->withInput($request->except('selfie_with_id'))->withErrors([
                    'nin' => __('email_recovery.errors.too_many', ['minutes' => ceil(RateLimiter::availableIn($key) / 60)]),
                ]);
            }
        }
        foreach (array_keys($keys) as $key) {
            RateLimiter::hit($key, 3600);
        }

        $validated = $request->validate(
            EmailRecoveryService::rules(),
            EmailRecoveryService::messages(),
            EmailRecoveryService::attributes(),
        );

        $this->recovery->submit($validated, $request->file('selfie_with_id'), $request->ip());

        return redirect()->route('email-recovery.create')
            ->with('email_recovery_submitted', true)
            ->with('success', __('email_recovery.flash.submitted'));
    }

    /** Look up the latest request for a NIN (rendered on the same page). */
    public function status(Request $request): View
    {
        $validated = $request->validate(
            ['status_nin' => ['required', 'digits:18']],
            [],
            ['status_nin' => __('email_recovery.form.nin')],
        );

        return view('auth.recover-email', [
            'lookupDone' => true,
            'lookup' => $this->recovery->latestForNin($validated['status_nin']),
        ]);
    }
}
