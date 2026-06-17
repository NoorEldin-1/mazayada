<?php

namespace App\Http\Middleware;

use App\Enums\KycStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * KYC gate for the mobile API — the JSON-returning counterpart of the web
 * KycVerified middleware. Returns a 403 JSON error (rendered by the API exception
 * handler) instead of redirecting to the KYC page. Applied after auth:sanctum, so
 * the user is always present; the hard blocks (blacklist/lock) are already covered
 * by EnsureActiveAccount but kept here as defence in depth.
 */
class ApiKycVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, __('common.api.unauthenticated'));
        }

        if ($user->isBlacklisted() || $user->isLocked()) {
            abort(403, __('kyc.not_authorized'));
        }

        if ($user->kyc_status !== KycStatus::COMPLETE) {
            $message = match ($user->kyc_status) {
                KycStatus::UNDER_REVIEW => __('kyc.flash_under_review'),
                KycStatus::REJECTED => __('kyc.flash_rejected'),
                KycStatus::SUSPENDED => __('kyc.flash_suspended'),
                default => __('kyc.complete_required'),
            };

            abort(403, $message);
        }

        return $next($request);
    }
}
