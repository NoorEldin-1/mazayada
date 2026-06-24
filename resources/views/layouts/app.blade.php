<!DOCTYPE html>
<html lang="{{ locale_lang() }}" dir="{{ locale_dir() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('common.app_name')) — {{ __('auth.left_badge') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/mazayada.css?v={{ filemtime(public_path('css/mazayada.css')) }}">
    @stack('styles')
</head>
<body>

{{-- ===== Announcement Bar ===== --}}
<div class="annbar">
    <div class="container">
        <div class="row">
            <span class="item">{{ __('common.platform_full') }}</span>
            <span class="sp">
                <span class="item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span class="num">023-567-1234</span>
                </span>
                <span class="item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    contact@mazayada.dz
                </span>
            </span>
        </div>
    </div>
</div>

{{-- ===== Header ===== --}}
<header class="hd">
    <div class="container">
        <div class="row">
            {{-- Mobile menu toggle --}}
            <button class="nav-burger" type="button" aria-label="{{ __('nav.menu') }}" aria-controls="primaryNav" aria-expanded="false" data-nav-burger>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            {{-- Brand --}}
            <a href="{{ route('home') }}" class="brand" style="text-decoration:none">
                <div class="brand-mark">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 17.5 3 3 3-3"/><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/></svg>
                </div>
                <div>
                    <div class="brand-name">{{ __('common.app_name') }}</div>
                    <div class="brand-sub">{{ __('common.app_subtitle') }}</div>
                </div>
            </a>

            {{-- Navigation (desktop dropdowns ⇄ mobile drawer) --}}
            <nav class="nav" id="primaryNav" data-nav aria-label="{{ __('nav.menu') }}">
                {{-- Drawer header (mobile only) --}}
                <div class="nav-head">
                    <span class="brand-name">{{ __('common.app_name') }}</span>
                    <button class="nav-close" type="button" aria-label="{{ __('common.close') }}" data-nav-close>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'on' : '' }}">{{ __('nav.home') }}</a>

                {{-- المزايدات --}}
                <div class="nav-item has-dd {{ request()->routeIs('auctions.*') ? 'on' : '' }}">
                    <button type="button" class="nav-trigger" aria-haspopup="true" aria-expanded="false" data-dd-trigger>
                        {{ __('nav.menu_auctions') }}
                        <svg class="dd-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="nav-dd">
                        <div class="nav-dd-inner">
                            <a href="{{ route('auctions.index') }}" class="dd-link rich">
                                <span class="dd-ic ic-mint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg></span>
                                <span class="dd-tx"><b>{{ __('nav.all_auctions') }}</b><small>{{ __('nav.all_auctions_desc') }}</small></span>
                            </a>
                            <a href="{{ route('auctions.index', ['type' => \App\Enums\AuctionType::SALE->value]) }}" class="dd-link rich">
                                <span class="dd-ic ic-gold"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-8 8a2.12 2.12 0 0 1-3-3l8-8"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg></span>
                                <span class="dd-tx"><b>{{ __('nav.public_auctions') }}</b><small>{{ __('nav.public_auctions_desc') }}</small></span>
                            </a>
                            <a href="{{ route('auctions.index', ['type' => \App\Enums\AuctionType::LEASE->value]) }}" class="dd-link rich">
                                <span class="dd-ic ic-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0"/><circle cx="16.5" cy="17.5" r="2.5"/><path d="M18.3 19.3 21 22"/></svg></span>
                                <span class="dd-tx"><b>{{ __('nav.public_rentals') }}</b><small>{{ __('nav.public_rentals_desc') }}</small></span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- الخدمات --}}
                <div class="nav-item has-dd">
                    <button type="button" class="nav-trigger" aria-haspopup="true" aria-expanded="false" data-dd-trigger>
                        {{ __('nav.menu_services') }}
                        <svg class="dd-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="nav-dd">
                        <div class="nav-dd-inner">
                            <a href="{{ route('how-it-works') }}" class="dd-link rich">
                                <span class="dd-ic ic-violet"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg></span>
                                <span class="dd-tx"><b>{{ __('nav.identity_verification') }}</b><small>{{ __('nav.identity_verification_desc') }}</small></span>
                            </a>
                            <a href="{{ route('how-it-works') }}" class="dd-link rich">
                                <span class="dd-ic ic-rose"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg></span>
                                <span class="dd-tx"><b>{{ __('nav.appeals_system') }}</b><small>{{ __('nav.appeals_system_desc') }}</small></span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- المنصة --}}
                <div class="nav-item has-dd {{ request()->routeIs('about') || request()->routeIs('how-it-works') ? 'on' : '' }}">
                    <button type="button" class="nav-trigger" aria-haspopup="true" aria-expanded="false" data-dd-trigger>
                        {{ __('nav.menu_platform') }}
                        <svg class="dd-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="nav-dd">
                        <div class="nav-dd-inner cols-1">
                            <a href="{{ route('about') }}" class="dd-link">
                                <span class="dd-ic ic-mint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg></span>
                                {{ __('nav.about_platform') }}
                            </a>
                            <a href="{{ route('how-it-works') }}" class="dd-link">
                                <span class="dd-ic ic-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
                                {{ __('nav.how_it_works') }}
                            </a>
                        </div>
                    </div>
                </div>

                {{-- قانوني --}}
                <div class="nav-item has-dd {{ request()->routeIs('legal.*') ? 'on' : '' }}">
                    <button type="button" class="nav-trigger" aria-haspopup="true" aria-expanded="false" data-dd-trigger>
                        {{ __('nav.menu_legal') }}
                        <svg class="dd-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="nav-dd">
                        <div class="nav-dd-inner cols-1">
                            <a href="{{ route('legal.terms') }}" class="dd-link">
                                <span class="dd-ic ic-sky"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                                {{ __('footer.terms') }}
                            </a>
                            <a href="{{ route('legal.privacy') }}" class="dd-link">
                                <span class="dd-ic ic-mint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                                {{ __('footer.privacy') }}
                            </a>
                            <a href="{{ route('legal.framework') }}" class="dd-link">
                                <span class="dd-ic ic-gold"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.287 1.288L3 12l5.8 1.9a2 2 0 0 1 1.288 1.287L12 21l1.9-5.8a2 2 0 0 1 1.287-1.288L21 12l-5.8-1.9a2 2 0 0 1-1.288-1.287Z"/></svg></span>
                                {{ __('footer.legal_framework') }}
                            </a>
                            <a href="{{ route('legal.notices') }}" class="dd-link">
                                <span class="dd-ic ic-rose"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
                                {{ __('footer.legal_notes') }}
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Drawer footer (mobile only): auth actions + language --}}
                <div class="nav-foot">
                    @auth
                        @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-block">{{ __('nav.dashboard') }}</a>
                        @else
                        <a href="{{ route('citizen.dashboard') }}" class="btn btn-primary btn-block">{{ __('nav.my_account') }}</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline btn-block">{{ __('nav.login') }}</a>
                        <a href="{{ route('register') }}" class="btn btn-primary btn-block">{{ __('nav.register') }}</a>
                    @endauth
                    <div class="nav-foot-lang"><x-lang-switcher /></div>
                </div>
            </nav>

            {{-- Actions --}}
            <div class="hd-actions">
                <x-lang-switcher />

                @auth
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-primary">{{ __('nav.dashboard') }}</a>
                    @else
                    <a href="{{ route('citizen.dashboard') }}" class="btn btn-sm btn-primary">{{ __('nav.my_account') }}</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-outline">{{ __('nav.login') }}</a>
                    <a href="{{ route('register') }}" class="btn btn-sm btn-primary">{{ __('nav.register') }}</a>
                @endauth
            </div>
        </div>
    </div>
</header>

{{-- Mobile drawer backdrop --}}
<div class="nav-backdrop" data-nav-backdrop hidden></div>

{{-- Main Content --}}
<main>
    @yield('content')
</main>

{{-- ===== Footer ===== --}}
<footer class="foot">
    <div class="container">
        <div class="foot-grid">
            <div class="foot-brand">
                <div class="brand" style="margin-bottom:4px">
                    <div class="brand-mark">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 17.5 3 3 3-3"/><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/></svg>
                    </div>
                    <div class="brand-name">{{ __('common.app_name') }}</div>
                </div>
                <p>{{ __('footer.about_text') }}</p>
                <div class="foot-soc">
                    <a href="https://www.facebook.com/mazayada.dz" target="_blank" rel="noopener noreferrer" aria-label="{{ __('footer.follow_facebook') }}"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                    <a href="https://x.com/mazayada_dz" target="_blank" rel="noopener noreferrer" aria-label="{{ __('footer.follow_twitter') }}"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5 0-.28-.03-.56-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg></a>
                    <a href="https://www.linkedin.com/company/mazayada-dz" target="_blank" rel="noopener noreferrer" aria-label="{{ __('footer.follow_linkedin') }}"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg></a>
                </div>
            </div>
            <div class="foot-col">
                <h5>{{ __('footer.quick_links') }}</h5>
                <ul>
                    <li><a href="{{ route('home') }}">{{ __('nav.home') }}</a></li>
                    <li><a href="{{ route('auctions.index') }}">{{ __('nav.browse_auctions') }}</a></li>
                    <li><a href="{{ route('how-it-works') }}">{{ __('nav.how_it_works') }}</a></li>
                    <li><a href="{{ route('about') }}">{{ __('footer.about_platform') }}</a></li>
                </ul>
            </div>
            <div class="foot-col">
                <h5>{{ __('footer.services') }}</h5>
                <ul>
                    <li><a href="{{ route('auctions.index', ['type' => \App\Enums\AuctionType::SALE->value]) }}">{{ __('footer.service_auctions') }}</a></li>
                    <li><a href="{{ route('auctions.index', ['type' => \App\Enums\AuctionType::LEASE->value]) }}">{{ __('footer.service_rentals') }}</a></li>
                    <li><a href="{{ route('how-it-works') }}">{{ __('footer.service_kyc') }}</a></li>
                    <li><a href="{{ route('how-it-works') }}">{{ __('footer.service_appeals') }}</a></li>
                </ul>
            </div>
            <div class="foot-col">
                <h5>{{ __('footer.legal') }}</h5>
                <ul>
                    <li><a href="{{ route('legal.terms') }}">{{ __('footer.terms') }}</a></li>
                    <li><a href="{{ route('legal.privacy') }}">{{ __('footer.privacy') }}</a></li>
                    <li><a href="{{ route('legal.framework') }}">{{ __('footer.legal_framework') }}</a></li>
                    <li><a href="{{ route('legal.notices') }}">{{ __('footer.legal_notes') }}</a></li>
                </ul>
            </div>
            <div class="foot-col">
                <h5>{{ __('footer.contact_us') }}</h5>
                <ul>
                    <li style="display:flex;align-items:center;gap:6px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> {{ __('footer.address') }}</li>
                    <li style="display:flex;align-items:center;gap:6px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> <a href="tel:+213023567124" dir="ltr" class="num">023-567-1234</a></li>
                    <li style="display:flex;align-items:center;gap:6px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg> <a href="mailto:contact@mazayada.dz">contact@mazayada.dz</a></li>
                </ul>
            </div>
        </div>
        <div class="foot-btm">
            <span>{{ __('footer.rights', ['year' => date('Y'), 'app' => __('common.app_name')]) }}</span>
            <span class="ml">
                <a href="{{ route('legal.terms') }}">{{ __('footer.terms') }}</a>
                <a href="{{ route('legal.privacy') }}">{{ __('footer.privacy') }}</a>
                <a href="{{ route('legal.framework') }}">{{ __('footer.legal_framework') }}</a>
            </span>
        </div>
    </div>
</footer>

{{-- Primary navigation: dropdown + mobile drawer behaviour --}}
<script>
(function () {
    var nav = document.querySelector('[data-nav]');
    if (!nav) return;

    var burger = document.querySelector('[data-nav-burger]');
    var backdrop = document.querySelector('[data-nav-backdrop]');
    var items = Array.prototype.slice.call(nav.querySelectorAll('.nav-item.has-dd'));
    var mq = window.matchMedia('(max-width: 960px)');

    function closeDropdowns(except) {
        items.forEach(function (item) {
            if (item === except) return;
            item.classList.remove('open');
            var t = item.querySelector('[data-dd-trigger]');
            if (t) t.setAttribute('aria-expanded', 'false');
        });
    }

    function openDrawer() {
        document.documentElement.classList.add('nav-open');
        if (backdrop) backdrop.hidden = false;
        if (burger) burger.setAttribute('aria-expanded', 'true');
    }
    function closeDrawer() {
        document.documentElement.classList.remove('nav-open');
        if (backdrop) backdrop.hidden = true;
        if (burger) burger.setAttribute('aria-expanded', 'false');
        closeDropdowns(null);
    }

    if (burger) burger.addEventListener('click', function () {
        document.documentElement.classList.contains('nav-open') ? closeDrawer() : openDrawer();
    });
    if (backdrop) backdrop.addEventListener('click', closeDrawer);

    var closeBtn = nav.querySelector('[data-nav-close]');
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

    // Dropdown triggers — click to toggle (touch + mobile accordion + keyboard).
    items.forEach(function (item) {
        var trigger = item.querySelector('[data-dd-trigger]');
        if (!trigger) return;
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            var willOpen = !item.classList.contains('open');
            closeDropdowns(item);
            item.classList.toggle('open', willOpen);
            trigger.setAttribute('aria-expanded', String(willOpen));
        });
    });

    // Close open dropdowns when clicking outside (desktop).
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.nav-item')) closeDropdowns(null);
    });

    // Escape closes everything.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeDropdowns(null); closeDrawer(); }
    });

    // Reset state when crossing the desktop/mobile boundary.
    mq.addEventListener('change', function () { closeDrawer(); });
})();
</script>

{{-- Header: intensify glass when scrolled, hide on scroll-down / reveal on scroll-up --}}
<script>
(function () {
    var hd = document.querySelector('.hd');
    if (!hd) return;

    var lastY = window.scrollY || 0;
    var ticking = false;

    function onScroll() {
        var y = window.scrollY || 0;
        hd.classList.toggle('is-scrolled', y > 10);

        var navOpen = document.documentElement.classList.contains('nav-open');
        if (!navOpen && y > hd.offsetHeight && y > lastY + 5) {
            hd.classList.add('is-hidden');      // scrolling down — slide up out of view
        } else if (y < lastY - 5 || y <= hd.offsetHeight) {
            hd.classList.remove('is-hidden');   // scrolling up / near top — reveal
        }
        lastY = y;
        ticking = false;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) { window.requestAnimationFrame(onScroll); ticking = true; }
    }, { passive: true });
    onScroll();
})();
</script>

@stack('scripts')
</body>
</html>
