@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'submit',
])
@php
    $base = 'ui-btn inline-flex items-center justify-center gap-2 rounded-xl font-semibold whitespace-nowrap leading-normal transition active:scale-[.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 disabled:opacity-50 disabled:pointer-events-none';

    $variants = [
        'primary' => 'ui-btn-primary text-white',
        'accent'  => 'bg-accent text-[#3a2a08] hover:brightness-95',
        'ghost'   => 'bg-surface text-ink border border-line hover:bg-bg-2',
        'outline' => 'bg-transparent border border-primary text-primary hover:bg-primary/5',
        'soft'    => 'bg-primary/10 text-primary hover:bg-primary/15',
        'danger'  => 'bg-danger text-white hover:brightness-95',
        'danger-ghost' => 'bg-surface border border-line text-danger hover:bg-danger/10',
    ];

    $sizes = [
        'sm' => 'text-xs px-3 py-2',
        'md' => 'text-sm px-4 py-2.5',
        'lg' => 'text-[15px] px-5 py-3 rounded-2xl',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
