<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional bearer-token resolution for PUBLIC API routes.
 *
 * The public endpoints (auction browse/detail) must stay reachable by guests, so
 * they cannot sit behind `auth:sanctum`. Without a guard, however, Laravel falls
 * back to the DEFAULT guard (`web`, session-based) when a controller calls
 * `$request->user()` — and a mobile client has no session, so an authenticated
 * caller looked exactly like a guest (meta.viewer was always null).
 *
 * This middleware resolves the Sanctum token when one is present and promotes
 * that user onto the request. It NEVER rejects: a missing, expired, revoked or
 * wrong-ability token simply leaves the request anonymous, preserving the public
 * contract of these routes.
 *
 * Mirrors the guarantees of the authenticated groups:
 *   - the token must carry the `access` ability (a REFRESH token is not a session)
 *   - the account must not be suspended/blacklisted (EnsureActiveAccount parity)
 *
 * Uses auth('sanctum') rather than the `auth:sanctum` middleware precisely so it
 * cannot reject, and it deliberately does NOT call shouldUse() — promoting the
 * user onto the request is enough, and switching the default guard would change
 * how spatie/laravel-permission resolves roles (see config/auth.php).
 */
class ResolveOptionalToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            return $next($request);
        }

        $user = auth('sanctum')->user();

        if ($user && $this->isUsable($user)) {
            $request->setUserResolver(fn () => $user);
        }

        return $next($request);
    }

    /** A token/account combination we are willing to treat as "signed in". */
    private function isUsable(object $user): bool
    {
        $token = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        // Transient tokens (Sanctum::actingAs in tests) can("*") — allowed.
        if ($token && ! $token->can('access')) {
            return false;
        }

        return ! $user->isBlacklisted()
            && ! $user->isLocked()
            && $user->account_status === AccountStatus::ACTIVE;
    }
}
