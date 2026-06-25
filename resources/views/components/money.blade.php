@props(['centimes' => 0, 'short' => false])
@php
    // Render a DZD amount as an isolated, non-wrapping unit so RTL never reorders
    // or splits it. The markup (.money > .amt + .cur) and the currency-side logic
    // live in dzd_html() + the .money CSS, shared with auction.js so server- and
    // client-rendered prices stay identical. Attribute merge keeps callers able to
    // add classes/styles to the outer .money span.
    $centimes = (int) ($centimes ?? 0);
    $dinars = intdiv($centimes, 100);
    $currency = __('common.currency');

    if ($short && $dinars >= 1_000_000) {
        $amount = rtrim(rtrim(number_format($dinars / 1_000_000, 1, '.', ''), '0'), '.') . __('common.million_suffix');
    } elseif ($short && $dinars >= 1_000) {
        $amount = rtrim(rtrim(number_format($dinars / 1_000, 1, '.', ''), '0'), '.') . __('common.thousand_suffix');
    } else {
        $amount = number_format($dinars, 0, ',', ' ');
    }
@endphp
<span {{ $attributes->merge(['class' => 'money']) }}><span class="amt">{{ $amount }}</span> <span class="cur">{{ $currency }}</span></span>
