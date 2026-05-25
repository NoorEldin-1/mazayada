<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorize against Spatie roles (which are now the source of truth).
 * Falls back to the legacy `role` column for backward compatibility until
 * all callers have been migrated to spatie role names.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->hasAnyRole($roles)) {
            return $next($request);
        }

        // Legacy fallback — keeps the older single-column role check working
        // until all controllers are using spatie roles directly.
        if ($user->role && in_array($user->role->value, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Unauthorized.');
    }
}
