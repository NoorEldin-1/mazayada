<!DOCTYPE html>
<html lang="{{ locale_lang() }}" dir="{{ locale_dir() }}"
      data-theme="{{ request()->cookie('theme') === 'dark' ? 'dark' : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('dashboard.nav_dashboard')) &mdash; {{ __('common.app_name') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/mazayada.css?v={{ filemtime(public_path('css/mazayada.css')) }}">
    @vite(['resources/css/dashboard.css', 'resources/js/dashboard.js'])
    @stack('styles')
</head>
<body class="bg-bg text-ink antialiased min-h-screen">

{{-- Header --}}
<header class="sticky top-0 z-40 bg-surface/90 backdrop-blur border-b border-line">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-7 flex items-center gap-4 sm:gap-6 py-3">
        {{-- Brand --}}
        <a href="/" class="flex items-center gap-2.5 shrink-0">
            <span class="block">
                <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
                    <rect width="36" height="36" rx="8" fill="url(#czBrandGrad)"/>
                    <path d="M10 26L18 10L22 18H26L18 26H10Z" fill="white" opacity="0.9"/>
                    <path d="M12 24L18 12L21 18" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                    <defs>
                        <linearGradient id="czBrandGrad" x1="0" y1="0" x2="36" y2="36">
                            <stop stop-color="#1B4D3E"/>
                            <stop offset="1" stop-color="#2D6A4F"/>
                        </linearGradient>
                    </defs>
                </svg>
            </span>
            <span class="font-bold text-lg text-primary">{{ __('common.app_name') }}</span>
        </a>

        {{-- Nav --}}
        <nav class="hidden md:flex items-center gap-1">
            <a href="/" class="px-3.5 py-2 rounded-lg text-sm font-medium text-ink-2 hover:bg-bg-2 hover:text-ink transition">{{ __('nav.home') }}</a>
            <a href="/auctions" class="px-3.5 py-2 rounded-lg text-sm font-medium text-ink-2 hover:bg-bg-2 hover:text-ink transition">{{ __('nav.browse_auctions') }}</a>
        </nav>

        {{-- Actions --}}
        <div class="flex items-center gap-2.5 ms-auto">
            {{-- Notifications (kept outside the menu so its unread badge stays visible) --}}
            <a href="{{ route('citizen.notifications') }}" class="relative inline-grid place-items-center size-9 rounded-lg text-ink-2 bg-bg border border-line hover:bg-bg-2 hover:text-ink transition" aria-label="{{ __('dashboard.nav_notifications') }}">
                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                @if(auth()->check() && auth()->user()->unreadNotificationsCount() > 0)
                    <span class="absolute -top-1 -end-1 min-w-[18px] h-[18px] px-1 grid place-items-center rounded-full bg-danger text-white text-[10px] font-bold leading-none">{{ auth()->user()->unreadNotificationsCount() }}</span>
                @endif
            </a>

            {{-- Account menu (language + theme + profile + logout) --}}
            <x-ui.user-menu
                :name="auth()->check() ? auth()->user()->name : ''"
                :initial="auth()->check() ? mb_substr(auth()->user()->name, 0, 1) : '?'">
                <x-slot:badge>
                    <x-kyc-status-badge />
                </x-slot:badge>
                <x-slot:meta>
                    <x-kyc-status-pill />
                    <x-commercial-register-badge />
                </x-slot:meta>

                <x-ui.action-menu.item :href="route('citizen.profile')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>{{ __('dashboard.nav_profile') }}</span>
                </x-ui.action-menu.item>
            </x-ui.user-menu>
        </div>
    </div>
</header>

{{-- Dashboard grid --}}
<div class="max-w-[1280px] mx-auto px-4 sm:px-7 py-6 lg:grid lg:grid-cols-[16rem_1fr] lg:gap-6 lg:items-start">
    {{-- Sidebar --}}
    <aside class="bg-surface border border-line rounded-2xl p-3 mb-5 lg:mb-0 lg:sticky lg:top-[88px]">
        {{-- User card --}}
        <div class="flex items-center gap-3 p-2.5 mb-2">
            <div class="grid place-items-center size-11 rounded-xl bg-primary/10 text-primary font-bold">
                {{ auth()->check() ? mb_substr(auth()->user()->name, 0, 1) : '?' }}
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="text-sm font-bold text-ink truncate">{{ auth()->check() ? auth()->user()->name : '' }}</span>
                    <x-kyc-status-badge />
                </div>
                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                    <x-kyc-status-pill />
                    <x-commercial-register-badge />
                </div>
            </div>
        </div>

        {{-- Navigation — 2-up grid on mobile keeps the stacked sidebar compact
             (all links visible without a tall scroll); reverts to a vertical
             list on desktop where the sidebar is a fixed column. --}}
        <nav class="grid grid-cols-2 gap-1.5 lg:flex lg:flex-col lg:gap-1">
            <x-ui.nav-link :href="route('citizen.dashboard')" :active="request()->routeIs('citizen.dashboard')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                <span>{{ __('dashboard.nav_dashboard') }}</span>
            </x-ui.nav-link>
            <x-ui.nav-link :href="route('citizen.my-auctions')" :active="request()->routeIs('citizen.my-auctions')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                <span>{{ __('dashboard.nav_my_auctions') }}</span>
            </x-ui.nav-link>
            <x-ui.nav-link :href="route('citizen.reports')" :active="request()->routeIs('citizen.reports')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                <span>{{ __('dashboard.nav_reports') }}</span>
            </x-ui.nav-link>
            <x-ui.nav-link :href="route('citizen.kyc')" :active="request()->routeIs('citizen.kyc')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>{{ __('dashboard.nav_identity') }}</span>
            </x-ui.nav-link>
            <x-ui.nav-link :href="route('citizen.commercial-register')" :active="request()->routeIs('citizen.commercial-register*')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><line x1="9" y1="9" x2="9" y2="9.01"/><line x1="9" y1="13" x2="9" y2="13.01"/></svg>
                <span>{{ __('dashboard.nav_commercial_register') }}</span>
            </x-ui.nav-link>
            <x-ui.nav-link :href="route('citizen.appeals')" :active="request()->routeIs('citizen.appeals')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <span>{{ __('dashboard.nav_appeals') }}</span>
            </x-ui.nav-link>
            <x-ui.nav-link :href="route('citizen.notifications')" :active="request()->routeIs('citizen.notifications')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span>{{ __('dashboard.nav_notifications') }}</span>
                @if(auth()->check() && auth()->user()->unreadNotificationsCount() > 0)
                    <span class="ms-auto min-w-[20px] h-5 px-1.5 grid place-items-center rounded-full bg-danger text-white text-[11px] font-bold leading-none">{{ auth()->user()->unreadNotificationsCount() }}</span>
                @endif
            </x-ui.nav-link>
            <x-ui.nav-link :href="route('citizen.profile')" :active="request()->routeIs('citizen.profile')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>{{ __('dashboard.nav_profile') }}</span>
            </x-ui.nav-link>
        </nav>
    </aside>

    {{-- Main content --}}
    <main class="min-w-0">
        @yield('content')
    </main>
</div>

<x-confirm-modal />

@stack('scripts')
</body>
</html>
