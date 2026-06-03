<?php

namespace App\Http\Middleware;

use App\Enums\KycStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KycVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Hard blocks first — a blacklisted or locked account is denied outright
        // regardless of KYC state (otherwise this check was unreachable).
        if ($user->isBlacklisted() || $user->isLocked()) {
            abort(403, __('kyc.not_authorized'));
        }

        if ($user->kyc_status !== KycStatus::COMPLETE) {
            // Send the citizen to the KYC page with a message that reflects where
            // they actually are in the flow.
            $message = match ($user->kyc_status) {
                KycStatus::UNDER_REVIEW => __('kyc.flash_under_review'),
                KycStatus::REJECTED => __('kyc.flash_rejected'),
                KycStatus::SUSPENDED => __('kyc.flash_suspended'),
                default => __('kyc.complete_required'),
            };

            return redirect()->route('citizen.kyc')->with('error', $message);
        }

        return $next($request);
    }
}
