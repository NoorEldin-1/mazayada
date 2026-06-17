<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runtime account-state guard for token-authenticated API requests.
 *
 * Even before a token is explicitly revoked (logout / password change / blacklist),
 * this rejects any request from a blacklisted, locked, suspended or banned account
 * on EVERY call — so a not-yet-revoked token is inert. Applied to the auth:sanctum
 * group; assumes auth has already resolved the user.
 */
class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->isBlacklisted()
            || $user->isLocked()
            || $user->account_status !== AccountStatus::ACTIVE)) {
            abort(403, __('common.api.account_inactive'));
        }

        return $next($request);
    }
}
