@props([
    'href' => '#',
    'active' => false,
    'tone' => 'default',
])
@php
    $tones = [
        // White-on-green sidebar (admin).
        'onPrimary' => [
            'idle' => 'text-white/70 hover:bg-white/10 hover:text-white [&>svg]:opacity-70',
            'on'   => 'bg-white/15 text-white font-semibold [&>svg]:opacity-100',
        ],
        // Ink-on-surface sidebar (citizen).
        'default' => [
            'idle' => 'text-ink-2 hover:bg-bg-2 hover:text-ink',
            'on'   => 'bg-primary/10 text-primary font-semibold',
        ],
    ];
    $t = $tones[$tone] ?? $tones['default'];
    $classes = 'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition [&>svg]:size-5 [&>svg]:shrink-0 '
        .($active ? $t['on'] : $t['idle']);
@endphp

<a href="{{ $href }}"
   @if ($active) aria-current="page" @endif
   {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
