@props([
    'label' => null,
])
{{--
    A collapsible sub-group inside <x-ui.action-menu>. Renders a toggle row that
    expands/collapses its child items IN PLACE (no fly-out) — the safest pattern
    for a position:fixed panel across RTL/LTR and mobile.

    The toggle is deliberately NOT role="menuitem": the action-menu engine closes
    the whole panel when a menuitem is clicked, and a sub-group toggle must keep
    the panel open. dashboard.js handles [data-act-subtoggle] separately.

    Usage:
        <x-ui.action-menu.submenu :label="__('...')">
            <x-ui.action-menu.item :action="route('...')">…</x-ui.action-menu.item>
            <x-ui.action-menu.item :href="route('...')">…</x-ui.action-menu.item>
        </x-ui.action-menu.submenu>
--}}
@php
    $subId = 'actsub-'.\Illuminate\Support\Str::random(6);
@endphp
<div class="act-menu__sub" data-act-sub>
    <button type="button"
            class="act-menu__item act-menu__subtoggle"
            data-act-subtoggle
            aria-haspopup="true"
            aria-expanded="false"
            aria-controls="{{ $subId }}">
        <span>{{ $label }}</span>
        <svg class="act-menu__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="6 9 12 15 18 9"/>
        </svg>
    </button>
    <div class="act-menu__subbody" id="{{ $subId }}" data-act-sub-body hidden>
        {{ $slot }}
    </div>
</div>
