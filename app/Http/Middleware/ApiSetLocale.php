<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sessionless locale resolution for the mobile API. Mirrors the web SetLocale
 * middleware but reads the locale from request headers instead of the session.
 *
 * Priority (first match wins):
 *   1. ?lang= query param          — explicit override (parity with web, eases testing)
 *   2. X-Locale header             — the app's preferred locale
 *   3. Accept-Language header      — first tag, normalised (e.g. "ar-DZ" -> "ar")
 *   4. authenticated user's stored preference
 *   5. config('locales.default')   — platform default (Arabic)
 */
class ApiSetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = (array) config('locales.supported', ['ar']);
        $default = (string) config('locales.default', 'ar');

        $locale = $request->query('lang')
            ?: $request->header('X-Locale')
            ?: $this->fromAcceptLanguage($request->header('Accept-Language'))
            ?: $request->user()?->locale;

        if (! in_array($locale, $supported, true)) {
            $locale = $default;
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    /**
     * Pull the primary language subtag from an Accept-Language header.
     * "fr-FR,fr;q=0.9,en;q=0.8" -> "fr".
     */
    private function fromAcceptLanguage(?string $header): ?string
    {
        if (! $header) {
            return null;
        }

        $first = trim(explode(',', $header)[0]);
        $primary = strtolower(trim(explode(';', $first)[0]));

        return explode('-', $primary)[0] ?: null;
    }
}
