@props([
    'label' => '',
    'value' => '',
    'tone' => 'mint',
    'hint' => null,
    'money' => false,   // when true, $value is centimes → rendered as a .money unit
    'layout' => 'inline', // 'inline' (icon beside text) | 'stacked' (icon top-end, text start-aligned)
])
@php
    $tones = [
        'mint'   => 'bg-primary/12 text-primary',
        'gold'   => 'bg-accent/15 text-accent-2',
        'blue'   => 'bg-info/12 text-info',
        'danger' => 'bg-danger/12 text-danger',
        'ok'     => 'bg-ok/12 text-ok',
        'violet' => 'bg-[color-mix(in_oklab,#8B6DD9_15%,transparent)] text-[#8B6DD9]',
    ];
    $iconClasses = $tones[$tone] ?? $tones['mint'];
@endphp

@if ($layout === 'stacked')
    {{-- Stacked: icon pinned to the top-end corner, then a start-aligned
         label → value → hint column that mirrors cleanly in RTL and LTR. --}}
    <div {{ $attributes->merge(['class' => 'ui-card p-5 text-start']) }}>
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="text-[13px] text-muted font-medium leading-snug">{{ $label }}</div>
            <div class="grid place-items-center size-11 rounded-2xl shrink-0 {{ $iconClasses }} [&>svg]:size-6">
                {{ $slot }}
            </div>
        </div>
        <div class="text-[26px] sm:text-[28px] font-bold tracking-tight num text-ink leading-none">{{ $money ? dzd_html((int) $value) : $value }}</div>
        @if ($hint)
            <div class="text-xs text-muted mt-2 leading-snug">{{ $hint }}</div>
        @endif
    </div>
@else
    <div {{ $attributes->merge(['class' => 'ui-card p-5 flex items-start gap-4']) }}>
        <div class="grid place-items-center size-12 rounded-2xl shrink-0 {{ $iconClasses }} [&>svg]:size-6">
            {{ $slot }}
        </div>
        <div class="min-w-0">
            <div class="text-[13px] text-muted font-medium mb-1">{{ $label }}</div>
            <div class="text-[26px] sm:text-[28px] font-bold tracking-tight num text-ink leading-none">{{ $money ? dzd_html((int) $value) : $value }}</div>
            @if ($hint)
                <div class="text-xs text-muted mt-1.5">{{ $hint }}</div>
            @endif
        </div>
    </div>
@endif
