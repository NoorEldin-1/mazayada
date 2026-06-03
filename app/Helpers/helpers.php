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
