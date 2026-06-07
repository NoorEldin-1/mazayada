<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires staff to have a confirmed TOTP second factor before using the admin
 * dashboard (spec §8.4 — 2FA mandatory for admin roles). Controlled by the
 * 'security.enforce_admin_2fa' setting (off by default so local/dev isn't
 * locked out). Redirects to the setup page rather than 403-ing, so an admin can
 * never lock themselves out.
 */
class EnsureTwoFactorForAdmins
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! setting('security.enforce_admin_2fa', false)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && $user->isStaff() && ! $user->two_factor_confirmed_at) {
            return redirect()->route('two-factor.setup')
                ->with('status', __('auth.two_factor_required'));
        }

        return $next($request);
    }
}
