@props([
    'id',
    'title' => null,
    'size' => 'md',
])
{{--
    Generic form-hosting modal — the destination for row actions that need a
    multi-field form (blacklist reason, delivery scheduling, inspection answer,
    appeal decision). Reuses the shared .mdl-overlay backdrop + animations from
    dashboard.css, adds a form-friendly (start-aligned, header + scrollable body)
    box variant, and is driven by the delegated modal controller in dashboard.js.

    Open it from an <x-ui.action-menu.item> (or any element) via
    `data-modal-target="#{{ '$id' }}"`. Closes on the ✕ button, backdrop click,
    or Escape.

    Usage:
        <x-ui.modal id="blacklist-{{ '$user->id' }}" :title="__('...')">
            <form method="POST" action="...">@csrf … </form>
        </x-ui.modal>

    size: md (default, 480px) | lg (620px, for the appeals decision card).
--}}
<div class="mdl-overlay" id="{{ $id }}" data-modal role="dialog" aria-modal="true"
     aria-hidden="true" @if($title) aria-labelledby="{{ $id }}-title" @endif>
    <div class="mdl-box mdl-box--form {{ $size === 'lg' ? 'mdl-box--lg' : '' }}" data-modal-box>
        <div class="mdl-form-head">
            <h3 class="mdl-form-title" @if($title) id="{{ $id }}-title" @endif>{{ $title }}</h3>
            <button type="button" class="mdl-form-close" data-modal-close aria-label="{{ __('common.close') }}" title="{{ __('common.close') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="mdl-form-body">
            {{ $slot }}
        </div>
    </div>
</div>
