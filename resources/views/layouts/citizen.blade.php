<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') &mdash; مزايدة</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/mazayada.css">
    @stack('styles')
</head>
<body>

{{-- Citizen Dashboard Header --}}
<header class="hd hd-cz">
    <div class="hd-inner">
        {{-- Brand --}}
        <a href="/" class="hd-brand">
            <span class="hd-logo">
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
            <span class="hd-brand-txt">مزايدة</span>
        </a>

        {{-- Nav --}}
        <nav class="hd-nav">
            <a href="/" class="hd-link">الرئيسية</a>
            <a href="/auctions" class="hd-link">تصفح المزايدات</a>
        </nav>

        {{-- User Actions --}}
        <div class="hd-actions">
            {{-- Notifications --}}
            <button class="hd-notif">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                @if(auth()->check() && auth()->user()->unreadNotificationsCount() > 0)
                    <span class="hd-notif-badge">{{ auth()->user()->unreadNotificationsCount() }}</span>
                @endif
            </button>

            {{-- User Info --}}
            <div class="hd-user">
                <div class="hd-user-avatar">
                    {{ auth()->check() ? mb_substr(auth()->user()->name, 0, 1) : '?' }}
                </div>
                <span class="hd-user-name">{{ auth()->check() ? auth()->user()->name : '' }}</span>
            </div>

            {{-- Logout --}}
            <form method="POST" action="/logout" class="hd-logout-form">
                @csrf
                <button type="submit" class="hd-btn hd-btn-logout">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    خروج
                </button>
            </form>
        </div>
    </div>
</header>

{{-- Dashboard Grid --}}
<div class="cz-grid">
    {{-- Sidebar --}}
    <aside class="cz-side">
        {{-- User Card --}}
        <div class="cz-side-user">
            <div class="cz-side-avatar">
                {{ auth()->check() ? mb_substr(auth()->user()->name, 0, 1) : '?' }}
            </div>
            <div class="cz-side-user-info">
                <div class="cz-side-name">{{ auth()->check() ? auth()->user()->name : '' }}</div>
                <div class="cz-side-role">مستخدم</div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="cz-side-nav">
            <a href="/dashboard" class="cz-side-link {{ request()->routeIs('dashboard') && !request()->routeIs('dashboard.*') ? 'on' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                لوحة التحكم
            </a>
            <a href="/dashboard/my-auctions" class="cz-side-link {{ request()->routeIs('dashboard.my-auctions*') ? 'on' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                مزايداتي
            </a>
            <a href="/dashboard/kyc" class="cz-side-link {{ request()->routeIs('dashboard.kyc*') ? 'on' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                التحقق من الهوية
            </a>
            <a href="/dashboard/appeals" class="cz-side-link {{ request()->routeIs('dashboard.appeals*') ? 'on' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                طعوناتي
            </a>
            <a href="/dashboard/notifications" class="cz-side-link {{ request()->routeIs('dashboard.notifications*') ? 'on' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                الإشعارات
                @if(auth()->check() && auth()->user()->unreadNotificationsCount() > 0)
                    <span class="cz-side-badge">{{ auth()->user()->unreadNotificationsCount() }}</span>
                @endif
            </a>
            <a href="/dashboard/profile" class="cz-side-link {{ request()->routeIs('dashboard.profile*') ? 'on' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                الملف الشخصي
            </a>
        </nav>
    </aside>

    {{-- Main Content --}}
    <main class="cz-main">
        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>
