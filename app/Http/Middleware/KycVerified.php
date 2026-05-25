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

        if ($user->kyc_status !== KycStatus::COMPLETE) {
            return redirect()->route('citizen.kyc')
                ->with('error', __('يجب إكمال التحقق من الهوية قبل المتابعة.'));
        }

        if ($user->isBlacklisted() || $user->isLocked()) {
            abort(403, __('غير مصرح لك بهذا الإجراء.'));
        }

        return $next($request);
    }
}
