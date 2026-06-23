<!DOCTYPE html>
<html lang="{{ locale_lang() }}" dir="{{ locale_dir() }}"
      data-theme="{{ request()->cookie('theme') === 'dark' ? 'dark' : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('admin.panel')) — {{ __('common.app_name') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/mazayada.css?v={{ filemtime(public_path('css/mazayada.css')) }}">
    @vite(['resources/css/dashboard.css', 'resources/js/dashboard.js'])
    @stack('styles')
</head>
<body class="bg-bg text-ink antialiased">

{{-- Sidebar --}}
<aside class="dash-side fixed inset-y-0 start-0 z-40 w-64 flex flex-col bg-[var(--sidebar-bg)] text-white border-e border-white/5">
    <div class="flex items-center gap-3 px-5 py-[1.35rem] border-b border-white/10">
        <span class="grid place-items-center size-10 rounded-xl bg-white/15">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 17.5 3 3 3-3"/><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/></svg>
        </span>
        <div>
            <div class="text-lg font-bold leading-none">{{ __('common.app_name') }}</div>
            <div class="text-[11px] opacity-60 mt-1">{{ __('admin.panel') }}</div>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-2.5 py-3.5 flex flex-col gap-1">
        {{-- The platform dashboard surfaces global figures; entity (read-only)
             accounts live in "auctions & appeals only", so it's hidden for them. --}}
        @unless(auth()->user()->entity_id !== null)
        <x-ui.nav-link tone="onPrimary" :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            <span>{{ __('admin.nav_dashboard') }}</span>
        </x-ui.nav-link>
        @endunless

        @can('auctions.viewAny')
        <x-ui.nav-link tone="onPrimary" :href="route('admin.auctions.index')" :active="request()->routeIs('admin.auctions.index','admin.auctions.edit')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14.5 17.5 3 3 3-3"/><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/></svg>
            <span>{{ __('admin.nav_auctions') }}</span>
        </x-ui.nav-link>
        @endcan

        @can('users.viewAny')
        <x-ui.nav-link tone="onPrimary" :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>{{ __('admin.nav_users') }}</span>
        </x-ui.nav-link>
        @endcan

        @can('kyc.review')
        <x-ui.nav-link tone="onPrimary" :href="route('admin.kyc.index')" :active="request()->routeIs('admin.kyc.*')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>{{ __('admin.nav_kyc') }}</span>
            @if(($kycPendingCount ?? 0) > 0)
                <span class="ms-auto min-w-[20px] h-5 px-1.5 grid place-items-center rounded-full bg-danger text-white text-[11px] font-bold leading-none">{{ $kycPendingCount }}</span>
            @endif
        </x-ui.nav-link>
        @endcan

        @can('appeals.viewAny')
        <x-ui.nav-link tone="onPrimary" :href="route('admin.appeals.index')" :active="request()->routeIs('admin.appeals.*')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span>{{ __('admin.nav_appeals') }}</span>
        </x-ui.nav-link>
        @endcan

        @can('inspections.answer')
        <x-ui.nav-link tone="onPrimary" :href="route('admin.inspections.index')" :active="request()->routeIs('admin.inspections.*')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v3"/><path d="M11 14h.01"/></svg>
            <span>{{ __('admin.nav_inspections') }}</span>
        </x-ui.nav-link>
        @endcan

        @can('deliveries.manage')
        <x-ui.nav-link tone="onPrimary" :href="route('admin.deliveries.index')" :active="request()->routeIs('admin.deliveries.*')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            <span>{{ __('admin.nav_deliveries') }}</span>
        </x-ui.nav-link>
        @endcan

        @can('entities.manage')
        <x-ui.nav-link tone="onPrimary" :href="route('admin.entities.index')" :active="request()->routeIs('admin.entities.*')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
            <span>{{ __('admin.nav_entities') }}</span>
        </x-ui.nav-link>
        @endcan

        @can('entities.members.manage')
        <x-ui.nav-link tone="onPrimary" :href="route('admin.entity-staff.index')" :active="request()->routeIs('admin.entity-staff.*')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            <span>{{ __('admin.nav_entity_staff') }}</span>
        </x-ui.nav-link>
        @endcan

        @can('categories.manage')
        <x-ui.nav-link tone="onPrimary" :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span>{{ __('admin.nav_categories') }}</span>
        </x-ui.nav-link>
        @endcan

        @can('system.parameters.manage')
        <x-ui.nav-link tone="onPrimary" :href="route('admin.settings.index')" :active="request()->routeIs('admin.settings.*')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span>{{ __('admin.nav_settings') }}</span>
        </x-ui.nav-link>
        @endcan

        @can('system.auditlogs.view')
        <x-ui.nav-link tone="onPrimary" :href="route('admin.audit-logs')" :active="request()->routeIs('admin.audit-logs')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>{{ __('admin.nav_audit') }}</span>
        </x-ui.nav-link>
        @endcan

        @can('auctions.create')
        <div class="my-2 mx-1.5 h-px bg-white/10"></div>
        <a href="{{ route('admin.auctions.create') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold bg-accent text-[#3a2a08] hover:brightness-95 transition [&>svg]:size-5">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>{{ __('admin.create_auction') }}</span>
        </a>
        @endcan
    </nav>

    <div class="flex items-center gap-2.5 px-3.5 py-4 border-t border-white/10">
        <div class="grid place-items-center size-9 rounded-lg bg-white/15 font-bold text-sm shrink-0">{{ auth()->check() ? mb_substr(auth()->user()->first_name_ar, 0, 1) : '?' }}</div>
        <div class="min-w-0">
            <div class="text-[13px] font-semibold truncate">{{ auth()->check() ? auth()->user()->fullNameAr() : '' }}</div>
            <div class="text-[11px] opacity-60 truncate">{{ auth()->check() ? auth()->user()->role->label() : '' }}</div>
        </div>
    </div>
</aside>

{{-- Mobile drawer backdrop --}}
<div class="dash-backdrop fixed inset-0 z-30 bg-black/50 lg:hidden" data-drawer-close></div>

{{-- Main --}}
<div class="dash-main min-h-screen flex flex-col">
    <header class="dash-top sticky top-0 z-20 flex items-center justify-between gap-3 bg-surface border-b border-line px-5 sm:px-7 py-3.5">
        <div class="flex items-center gap-3 min-w-0">
            <x-ui.icon-button class="lg:hidden" data-drawer-toggle aria-label="{{ __('nav.menu') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </x-ui.icon-button>
            <h1 class="text-xl sm:text-[22px] font-bold truncate">@yield('page-title', __('admin.page_title_default'))</h1>
        </div>
        <div class="flex items-center gap-2.5">
            <x-ui.theme-toggle />
            <x-lang-switcher />
            <form method="POST" action="{{ route('logout') }}"
                  data-confirm="{{ __('nav.logout_confirm_message') }}"
                  data-confirm-title="{{ __('nav.logout_confirm_title') }}"
                  data-confirm-label="{{ __('nav.logout') }}">
                @csrf
                <x-ui.icon-button type="submit" title="{{ __('admin.logout') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </x-ui.icon-button>
            </form>
        </div>
    </header>

    <div class="flex-1 p-5 sm:p-7">
        @yield('content')
    </div>
</div>

<x-confirm-modal />

@stack('scripts')
</body>
</html>
