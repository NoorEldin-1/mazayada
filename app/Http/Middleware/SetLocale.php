<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    protected array $supported = ['ar', 'fr', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang');

        if (!$locale && $request->hasSession()) {
            $locale = $request->session()->get('locale', 'ar');
        }

        $locale = $locale ?: 'ar';

        if (!in_array($locale, $this->supported, true)) {
            $locale = 'ar';
        }

        app()->setLocale($locale);

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }
}
