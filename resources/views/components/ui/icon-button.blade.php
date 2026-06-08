@props([
    'href' => null,
    'type' => 'button',
])
@php
    $classes = 'inline-grid place-items-center size-9 rounded-lg text-ink-2 bg-bg border border-line hover:bg-bg-2 hover:text-ink transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 [&>svg]:size-[18px]';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
