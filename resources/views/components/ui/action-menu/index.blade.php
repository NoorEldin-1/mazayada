@props([
    'label' => null,
])
{{--
    Row-action dropdown — the unified "⋮" menu used in every admin table.

    Collapses the per-row action buttons into a single kebab trigger + a
    floating panel. The panel is position:fixed (positioned by JS from the
    trigger rect) so it escapes the table's `overflow-x:auto` clip and can
    flip above near the viewport bottom. RTL/LTR aware.

    Usage:
        <x-ui.action-menu>
            <x-ui.action-menu.item :href="route('...')">{{ __('common.view') }}</x-ui.action-menu.item>
            <x-ui.action-menu.item :action="route('...')" :confirm="__('...')" variant="danger">…</x-ui.action-menu.item>
            <x-ui.action-menu.item data-modal-target="#some-modal">…</x-ui.action-menu.item>
        </x-ui.action-menu>

    Item shapes are documented in action-menu/item.blade.php.
--}}
@php
    $menuId = 'actm-'.\Illuminate\Support\Str::random(8);
    $label = $label ?? __('common.actions_menu');
@endphp
<div class="act-menu" data-act-menu>
    <button type="button"
            class="act-menu__trigger"
            data-act-trigger
            aria-haspopup="true"
            aria-expanded="false"
            aria-controls="{{ $menuId }}"
            aria-label="{{ $label }}"
            title="{{ $label }}">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
        </svg>
    </button>
    <div class="act-menu__panel" id="{{ $menuId }}" data-act-panel role="menu" aria-label="{{ $label }}" hidden>
        {{ $slot }}
    </div>
</div>
