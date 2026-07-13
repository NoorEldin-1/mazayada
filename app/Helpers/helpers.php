<?php

if (! function_exists('locale_dir')) {
    /**
     * Text direction ('rtl' | 'ltr') for the given locale, or the active one.
     * Drives <html dir> and the body font in the layouts.
     */
    function locale_dir(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return (string) (config("locales.meta.{$locale}.dir") ?? 'rtl');
    }
}

if (! function_exists('locale_is_rtl')) {
    /** Whether the given/active locale is right-to-left. */
    function locale_is_rtl(?string $locale = null): bool
    {
        return locale_dir($locale) === 'rtl';
    }
}

if (! function_exists('locale_lang')) {
    /**
     * Value for the <html lang> attribute (BCP-47 style, e.g. "ar", "fr").
     */
    function locale_lang(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return str_replace('_', '-', $locale);
    }
}

if (! function_exists('locale_font_class')) {
    /**
     * Body font-family bucket for the active locale: 'arabic' | 'latin'.
     * Used to flip the typeface when switching between AR and FR/EN.
     */
    function locale_font_class(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return (string) (config("locales.meta.{$locale}.font") ?? 'arabic');
    }
}

if (! function_exists('invalidate_user_sessions')) {
    /**
     * Force-log-out a user everywhere by deleting their persisted sessions
     * (spec §8.4 — invalidate sessions on password change / blacklist /
     * suspension). Only meaningful for the database session driver; a no-op
     * otherwise (e.g. the array driver used in tests).
     */
    function invalidate_user_sessions(string $userId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        try {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $userId)
                ->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Session invalidation failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

if (! function_exists('setting')) {
    /**
     * Read a runtime platform parameter (spec §8.2). Resolution order:
     *   1. system_settings table (cached for the request lifetime, busted on write)
     *   2. config/mazayada.php (matching dot-key, e.g. 'bidding.extension_trigger_seconds')
     *   3. the provided $default
     *
     * The DB lookup is wrapped so that calling setting() before the table is
     * migrated (e.g. during early migrations) safely falls back to config.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        $all = \Illuminate\Support\Facades\Cache::rememberForever('system_settings', function () {
            try {
                return \App\Models\SystemSetting::all()
                    ->mapWithKeys(fn ($s) => [$s->key => $s->typedValue()])
                    ->all();
            } catch (\Throwable $e) {
                return [];
            }
        });

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return config('mazayada.'.$key, $default);
    }
}

if (! function_exists('human_filesize')) {
    /**
     * Human-readable file size from a byte count (e.g. 1536 => "1.5 KB").
     * Used by the document library to show each generated PDF's size. The unit
     * label is a plain ASCII token (KB/MB) — coherent in both RTL and LTR.
     */
    function human_filesize(int|null $bytes, int $precision = 1): string
    {
        $bytes = max(0, (int) $bytes);

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units));
        $value = $bytes / (1024 ** $power);
        $display = rtrim(rtrim(number_format($value, $precision, '.', ''), '0'), '.');

        return $display.' '.$units[$power - 1];
    }
}

if (! function_exists('mask_nin')) {
    /**
     * Partially mask a National Identity Number for display on generated
     * documents (spec §10.2 — "NIN partially masked, last 2 visible"). The full
     * NIN is never printed.
     */
    function mask_nin(?string $nin): string
    {
        $nin = (string) $nin;
        $len = strlen($nin);

        if ($len <= 2) {
            return $nin;
        }

        return str_repeat('*', $len - 2).substr($nin, -2);
    }
}

if (! function_exists('dinars')) {
    /**
     * Whole dinars from an integer-centimes amount. The money boundary for the
     * mobile API: services/DB/broadcasts use centimes, API resources expose
     * dinars (amounts are always whole dinars, so this is exact).
     */
    function dinars(int|null $centimes): int
    {
        return intdiv((int) $centimes, 100);
    }
}

if (! function_exists('dzd')) {
    /**
     * Format centimes to a DZD display string.
     *
     * Example: dzd(125000000) => "1 250 000 دج" (AR) / "1 250 000 DA" (FR/EN)
     */
    function dzd(int|null $centimes): string
    {
        $centimes = $centimes ?? 0;
        $dinars = intdiv($centimes, 100);

        $formatted = number_format($dinars, 0, ',', ' ');

        return $formatted . ' ' . __('common.currency');
    }
}

if (! function_exists('dzd_short')) {
    /**
     * Format centimes to a short DZD display string.
     *
     * Examples (AR):
     *   dzd_short(120000000) => "1.2م دج"  (millions)
     *   dzd_short(45000000)  => "450ك دج"  (thousands)
     *   dzd_short(75000)     => "750 دج"   (plain)
     */
    function dzd_short(int|null $centimes): string
    {
        $centimes = $centimes ?? 0;
        $dinars = intdiv($centimes, 100);
        $currency = __('common.currency');

        if ($dinars >= 1_000_000) {
            $value = $dinars / 1_000_000;
            $display = rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');

            return $display . __('common.million_suffix') . ' ' . $currency;
        }

        if ($dinars >= 1_000) {
            $value = $dinars / 1_000;
            $display = rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');

            return $display . __('common.thousand_suffix') . ' ' . $currency;
        }

        return $dinars . ' ' . $currency;
    }
}

if (! function_exists('dzd_html')) {
    /**
     * Money as the isolated `.money` markup — the SAME structure the <x-money>
     * Blade component and auction.js emit. Use this (with `{!! !!}`) for string /
     * array contexts that can't drop in the component (stat-tile value, generic
     * label=>value field lists). The CSS (`.money`) places the currency on the
     * document's reading side (AR: left, FR/EN: right) and keeps the amount LTR.
     *
     * Kept separate from dzd(): dzd() must stay a plain, control-char-free string
     * for the mobile JSON API. This returns HTML — never expose it through the API.
     *
     * Returns an HtmlString (Htmlable), so it renders raw through `{{ }}` while
     * sibling plain-text values in the same loop stay escaped — letting generic
     * label=>value field lists hold money + text safely without manual escaping.
     */
    function dzd_html(int|null $centimes, bool $short = false): \Illuminate\Support\HtmlString
    {
        $dinars = intdiv((int) ($centimes ?? 0), 100);
        $currency = __('common.currency');

        if ($short && $dinars >= 1_000_000) {
            $amount = rtrim(rtrim(number_format($dinars / 1_000_000, 1, '.', ''), '0'), '.') . __('common.million_suffix');
        } elseif ($short && $dinars >= 1_000) {
            $amount = rtrim(rtrim(number_format($dinars / 1_000, 1, '.', ''), '0'), '.') . __('common.thousand_suffix');
        } else {
            $amount = number_format($dinars, 0, ',', ' ');
        }

        return new \Illuminate\Support\HtmlString(
            '<span class="money"><span class="amt">' . e($amount) . '</span> <span class="cur">' . e($currency) . '</span></span>'
        );
    }
}

if (! function_exists('dzd_pdf')) {
    /**
     * Money for the mpdf-rendered documents (condition book / award / receipt).
     *
     * mpdf runs the bidi algorithm: a plain "4 000 000 دج" in an RTL document gets
     * its space-separated groups reversed ("000 000 4"). Wrapping just the amount
     * in an explicit LTR span keeps it a coherent token, and mpdf's bidi then sits
     * the currency on the document's reading side (AR: left, FR/EN: right) — same
     * outcome as the web .money unit, without flexbox (which mpdf doesn't support).
     *
     * Returns an HtmlString — render with `{!! !!}`. Never expose through the API.
     */
    function dzd_pdf(int|null $centimes, bool $short = false): \Illuminate\Support\HtmlString
    {
        $dinars = intdiv((int) ($centimes ?? 0), 100);
        $currency = __('common.currency');

        if ($short && $dinars >= 1_000_000) {
            $amount = rtrim(rtrim(number_format($dinars / 1_000_000, 1, '.', ''), '0'), '.') . __('common.million_suffix');
        } elseif ($short && $dinars >= 1_000) {
            $amount = rtrim(rtrim(number_format($dinars / 1_000, 1, '.', ''), '0'), '.') . __('common.thousand_suffix');
        } else {
            $amount = number_format($dinars, 0, ',', ' ');
        }

        return new \Illuminate\Support\HtmlString(
            '<span dir="ltr">' . e($amount) . '</span> ' . e($currency)
        );
    }
}

if (! function_exists('dzd_text')) {
    /**
     * Money for PLAIN-TEXT strings rendered in an RTL context where no HTML/CSS is
     * available — notification bodies, email copy, flash messages. A bare
     * "3 086 354 دج" dropped into an Arabic sentence has its space-separated groups
     * reversed by the bidi algorithm ("354 086 3"); wrapping the amount in a Unicode
     * LTR isolate (U+2066 … U+2069) keeps it a coherent token while the currency
     * stays a normal word after it (so دج sits on the number's reading side).
     *
     * Safe across channels (DB-stored body, email, mobile app): the isolates are
     * standard, invisible bidi controls. Distinct from dzd() so the API stays clean.
     */
    function dzd_text(int|null $centimes, bool $short = false): string
    {
        $dinars = intdiv((int) ($centimes ?? 0), 100);
        $currency = __('common.currency');

        if ($short && $dinars >= 1_000_000) {
            $amount = rtrim(rtrim(number_format($dinars / 1_000_000, 1, '.', ''), '0'), '.') . __('common.million_suffix');
        } elseif ($short && $dinars >= 1_000) {
            $amount = rtrim(rtrim(number_format($dinars / 1_000, 1, '.', ''), '0'), '.') . __('common.thousand_suffix');
        } else {
            $amount = number_format($dinars, 0, ',', ' ');
        }

        // U+2066 LEFT-TO-RIGHT ISOLATE … U+2069 POP DIRECTIONAL ISOLATE.
        return "\u{2066}" . $amount . "\u{2069}" . ' ' . $currency;
    }
}
