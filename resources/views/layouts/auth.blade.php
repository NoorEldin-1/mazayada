<!DOCTYPE html>
<html lang="{{ locale_lang() }}" dir="{{ locale_dir() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('common.app_name')) &mdash; {{ __('auth.left_badge') }}</title>
    <x-favicons />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/mazayada.css?v={{ filemtime(public_path('css/mazayada.css')) }}">
    @stack('styles')
</head>
<body>

{{-- Minimal Header (brand + language switcher) --}}
<header class="hd hd-auth">
    <div class="hd-inner" style="display:flex;align-items:center;justify-content:space-between;gap:16px">
        <a href="/" class="hd-brand">
            <img class="hd-logo" src="/images/brand/logo-light.png" width="982" height="320" alt="{{ __('common.app_name') }}">
        </a>
        <x-lang-switcher />
    </div>
</header>

{{-- Auth Shell: Split Screen --}}
<div class="auth-shell">
    {{-- Left Panel: Green gradient with testimonial --}}
    <div class="auth-left">
        <div class="auth-left-content">
            <div class="auth-left-badge">{{ __('auth.left_badge') }}</div>
            <h1 class="auth-left-title">{{ __('auth.left_title') }}</h1>
            <p class="auth-left-desc">{{ __('auth.left_desc') }}</p>

            <x-auth-carousel />

            <div class="auth-left-stats">
                <div class="auth-stat">
                    <span class="auth-stat-num">+2,500</span>
                    <span class="auth-stat-label">{{ __('auth.stat_active_auctions') }}</span>
                </div>
                <div class="auth-stat">
                    <span class="auth-stat-num">+15,000</span>
                    <span class="auth-stat-label">{{ __('auth.stat_registered_users') }}</span>
                </div>
                <div class="auth-stat">
                    <span class="auth-stat-num">48</span>
                    <span class="auth-stat-label">{{ __('auth.stat_wilayas') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Panel: Form Area --}}
    <div class="auth-right">
        <div class="auth-right-content">
            @yield('content')
        </div>
    </div>
</div>

@stack('scripts')
</body>
</html>
