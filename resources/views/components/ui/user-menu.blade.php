@props([
    'name' => '',
    'subtitle' => null,
    'initial' => '?',
    // 'header'  → light pill trigger (dashboard top bar, default)
    // 'sidebar' → full-width dark trigger pinned in the sidebar footer
    'context' => 'header',
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
@php($isSidebar = $context === 'sidebar')
<div class="act-menu usermenu {{ $isSidebar ? 'usermenu--sidebar' : '' }}" data-act-menu @if($isSidebar) data-act-portal @endif>
    <button type="button"
            class="usermenu__trigger {{ $isSidebar ? 'usermenu__trigger--sidebar' : '' }}"
            data-act-trigger
            aria-haspopup="true"
            aria-expanded="false"
            aria-controls="{{ $menuId }}"
            aria-label="{{ __('nav.account_menu') }}"
            title="{{ __('nav.account_menu') }}">
        @if($isSidebar)
            <span class="usermenu__avatar-wrap">
                <span class="usermenu__avatar usermenu__avatar--photo" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12.75a4.125 4.125 0 1 0 0-8.25 4.125 4.125 0 0 0 0 8.25Zm0 1.5c-3.66 0-6.75 2.09-6.75 4.87V21a.75.75 0 0 0 .75.75h12a.75.75 0 0 0 .75-.75v-1.88c0-2.78-3.09-4.87-6.75-4.87Z"/></svg>
                </span>
                <span class="usermenu__online" title="{{ __('nav.online') }}" aria-hidden="true"></span>
            </span>
            <span class="usermenu__identity">
                <span class="usermenu__name">{{ $name }}</span>
                @if($subtitle)<span class="usermenu__trigger-sub">{{ $subtitle }}</span>@endif
            </span>
        @else
            <span class="usermenu__avatar" aria-hidden="true">{{ $initial }}</span>
            <span class="usermenu__name">{{ $name }}</span>
        @endif
        <svg class="usermenu__caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="6 9 12 15 18 9"/>
        </svg>
    </button>

    <div class="act-menu__panel usermenu__panel" id="{{ $menuId }}" data-act-panel role="menu" aria-label="{{ __('nav.account_menu') }}" hidden>
        {{-- Identity header --}}
        <div class="usermenu__head">
            @if($isSidebar)
                <span class="usermenu__avatar-wrap">
                    <span class="usermenu__avatar usermenu__avatar--lg usermenu__avatar--photo" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12.75a4.125 4.125 0 1 0 0-8.25 4.125 4.125 0 0 0 0 8.25Zm0 1.5c-3.66 0-6.75 2.09-6.75 4.87V21a.75.75 0 0 0 .75.75h12a.75.75 0 0 0 .75-.75v-1.88c0-2.78-3.09-4.87-6.75-4.87Z"/></svg>
                    </span>
                    <span class="usermenu__online usermenu__online--lg" title="{{ __('nav.online') }}" aria-hidden="true"></span>
                </span>
            @else
                <span class="usermenu__avatar usermenu__avatar--lg" aria-hidden="true">{{ $initial }}</span>
            @endif
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

        {{-- Appearance (theme) — segmented light/dark control. Built with
             explicit CSS (see .theme-seg in dashboard.css) rather than
             Tailwind `dark:` utilities, which the isolated dashboard build
             was tree-shaking away, leaving the icon invisible. --}}
        @php($isDark = request()->cookie('theme') === 'dark')
        <div class="usermenu__row">
            <span class="usermenu__row-label">{{ __('nav.appearance') }}</span>
            <div class="theme-seg" role="group" aria-label="{{ __('common.toggle_theme') }}">
                <button type="button" class="theme-seg__btn {{ $isDark ? '' : 'is-on' }}" data-theme-set="light" aria-pressed="{{ $isDark ? 'false' : 'true' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                    <span>{{ __('nav.theme_light') }}</span>
                </button>
                <button type="button" class="theme-seg__btn {{ $isDark ? 'is-on' : '' }}" data-theme-set="dark" aria-pressed="{{ $isDark ? 'true' : 'false' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    <span>{{ __('nav.theme_dark') }}</span>
                </button>
            </div>
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
