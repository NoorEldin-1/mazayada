@props([
    'name' => '',
    'subtitle' => null,
    'initial' => '?',
])
{{--
    Account dropdown — the unified user menu in the dashboard headers
    (admin + citizen). Collapses the scattered header controls (language,
    theme, logout, identity) into one avatar-triggered panel.

    Reuses the shared row-action dropdown engine (data-act-menu /
    data-act-trigger / data-act-panel in resources/js/dashboard.js): the
    panel is position:fixed, JS-positioned from the trigger, RTL-aware,
    flips above near the viewport bottom, and closes on outside-click /
    Escape / scroll / resize. No new JS.

    The theme toggle inside is NOT a [role=menuitem], so switching theme
    keeps the panel open (the JS only auto-closes on menuitem activation).

    Usage: pass extra menu links (e.g. profile) as the default slot; they
    render above the appearance / language rows. The `badge` slot renders
    beside the name in the header (e.g. the KYC status badge).
--}}
@php($menuId = 'usermenu-'.\Illuminate\Support\Str::random(8))
<div class="act-menu" data-act-menu>
    <button type="button"
            class="usermenu__trigger"
            data-act-trigger
            aria-haspopup="true"
            aria-expanded="false"
            aria-controls="{{ $menuId }}"
            aria-label="{{ __('nav.account_menu') }}"
            title="{{ __('nav.account_menu') }}">
        <span class="usermenu__avatar" aria-hidden="true">{{ $initial }}</span>
        <span class="usermenu__name">{{ $name }}</span>
        <svg class="usermenu__caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="6 9 12 15 18 9"/>
        </svg>
    </button>

    <div class="act-menu__panel usermenu__panel" id="{{ $menuId }}" data-act-panel role="menu" aria-label="{{ __('nav.account_menu') }}" hidden>
        {{-- Identity header --}}
        <div class="usermenu__head">
            <span class="usermenu__avatar usermenu__avatar--lg" aria-hidden="true">{{ $initial }}</span>
            <div class="usermenu__id">
                <div class="usermenu__id-name">
                    <span class="truncate">{{ $name }}</span>
                    {{ $badge ?? '' }}
                </div>
                @if($subtitle)
                    <div class="usermenu__id-sub">{{ $subtitle }}</div>
                @endif
                @isset($meta)
                    <div class="usermenu__meta">{{ $meta }}</div>
                @endisset
            </div>
        </div>

        {{-- Optional extra links (e.g. profile) --}}
        @if(! $slot->isEmpty())
            <div class="usermenu__divider"></div>
            {{ $slot }}
        @endif

        <div class="usermenu__divider"></div>

        {{-- Appearance (theme) --}}
        <div class="usermenu__row">
            <span class="usermenu__row-label">{{ __('nav.appearance') }}</span>
            <x-ui.theme-toggle class="shrink-0" />
        </div>

        {{-- Language --}}
        <div class="usermenu__row">
            <span class="usermenu__row-label">{{ __('nav.language') }}</span>
            <x-lang-switcher />
        </div>

        <div class="usermenu__divider"></div>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}" class="act-menu__form"
              data-confirm="{{ __('nav.logout_confirm_message') }}"
              data-confirm-title="{{ __('nav.logout_confirm_title') }}"
              data-confirm-label="{{ __('nav.logout') }}"
              data-confirm-variant="danger">
            @csrf
            <button type="submit" role="menuitem" class="act-menu__item act-menu__item--danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>{{ __('nav.logout') }}</span>
            </button>
        </form>
    </div>
</div>
