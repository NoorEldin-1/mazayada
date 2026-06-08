@props([
    'variant' => 'muted',
    'dot' => false,
])
@php
    $variants = [
        'ok'      => 'bg-ok/12 text-ok',
        'success' => 'bg-ok/12 text-ok',
        'warn'    => 'bg-warn/15 text-warn',
        'danger'  => 'bg-danger/12 text-danger',
        'info'    => 'bg-info/12 text-info',
        'primary' => 'bg-primary/10 text-primary',
        'accent'  => 'bg-accent/15 text-accent-2',
        'muted'   => 'bg-bg-2 text-ink-2',
    ];
    $classes = 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold leading-none '
        .($variants[$variant] ?? $variants['muted']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full bg-current"></span>
    @endif
    {{ $slot }}
</span>
