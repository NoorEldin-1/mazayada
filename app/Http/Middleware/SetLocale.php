<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve and apply the active locale for every web request.
     *
     * Priority (first match wins):
     *   1. ?lang= query param  — an explicit switch on this request
     *   2. session('locale')   — what the visitor last chose
     *   3. authenticated user's stored preference — follows them across devices
     *   4. config('locales.default') — the platform default (Arabic)
     *
     * The resolved locale is persisted back to the session so it survives the
     * next request, and Carbon is aligned so relative dates ("منذ ساعة" /
     * "il y a une heure") render in the active language.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = (array) config('locales.supported', ['ar']);
        $default = (string) config('locales.default', 'ar');

        $locale = $request->query('lang');

        if (! $locale && $request->hasSession() && $request->session()->has('locale')) {
            $locale = $request->session()->get('locale');
        }

        if (! $locale && $request->user()) {
            $locale = $request->user()->locale;
        }

        if (! in_array($locale, $supported, true)) {
            $locale = $default;
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }
}
