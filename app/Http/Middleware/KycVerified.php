<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KycVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->kyc_status !== 'COMPLETE') {
            return redirect('/dashboard/kyc')
                ->with('error', __('يجب إكمال التحقق من الهوية قبل المتابعة.'));
        }

        return $next($request);
    }
}
