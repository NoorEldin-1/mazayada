<?php

if (! function_exists('dzd')) {
    /**
     * Format centimes to a DZD display string.
     *
     * Example: dzd(125000000) => "1 250 000 دج"
     */
    function dzd(int|null $centimes): string
    {
        $centimes = $centimes ?? 0;
        $dinars = intdiv($centimes, 100);

        $formatted = number_format($dinars, 0, ',', ' ');

        return $formatted . ' دج';
    }
}

if (! function_exists('dzd_short')) {
    /**
     * Format centimes to a short DZD display string.
     *
     * Examples:
     *   dzd_short(120000000) => "1.2م دج"  (millions)
     *   dzd_short(45000000)  => "450ك دج"  (thousands)
     *   dzd_short(75000)     => "750 دج"   (plain)
     */
    function dzd_short(int|null $centimes): string
    {
        $centimes = $centimes ?? 0;
        $dinars = intdiv($centimes, 100);

        if ($dinars >= 1_000_000) {
            $value = $dinars / 1_000_000;
            $display = rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');

            return $display . 'م دج';
        }

        if ($dinars >= 1_000) {
            $value = $dinars / 1_000;
            $display = rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');

            return $display . 'ك دج';
        }

        return $dinars . ' دج';
    }
}
