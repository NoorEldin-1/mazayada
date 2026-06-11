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

            {{-- Navigation --}}
            <nav class="nav">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'on' : '' }}">{{ __('nav.home') }}</a>
                <a href="{{ route('auctions.index') }}" class="{{ request()->routeIs('auctions.*') ? 'on' : '' }}">{{ __('nav.browse_auctions') }}</a>
                <a href="{{ route('how-it-works') }}" class="{{ request()->routeIs('how-it-works') ? 'on' : '' }}">{{ __('nav.how_it_works') }}</a>
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'on' : '' }}">{{ __('nav.about') }}</a>
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
                    <a href="#"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                    <a href="#"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5 0-.28-.03-.56-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg></a>
                    <a href="#"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg></a>
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
                    <li>{{ __('footer.service_auctions') }}</li>
                    <li>{{ __('footer.service_rentals') }}</li>
                    <li>{{ __('footer.service_kyc') }}</li>
                    <li>{{ __('footer.service_appeals') }}</li>
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
                    <li style="display:flex;align-items:center;gap:6px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> <span class="num">023-567-1234</span></li>
                    <li style="display:flex;align-items:center;gap:6px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg> contact@mazayada.dz</li>
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

@stack('scripts')
</body>
</html>
