<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'مزايدة') &mdash; المنصة الوطنية للمزايدات</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/mazayada.css">
    @stack('styles')
</head>
<body>

{{-- Minimal Header (brand only) --}}
<header class="hd hd-auth">
    <div class="hd-inner">
        <a href="/" class="hd-brand">
            <span class="hd-logo">
                <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
                    <rect width="36" height="36" rx="8" fill="url(#authBrandGrad)"/>
                    <path d="M10 26L18 10L22 18H26L18 26H10Z" fill="white" opacity="0.9"/>
                    <path d="M12 24L18 12L21 18" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                    <defs>
                        <linearGradient id="authBrandGrad" x1="0" y1="0" x2="36" y2="36">
                            <stop stop-color="#1B4D3E"/>
                            <stop offset="1" stop-color="#2D6A4F"/>
                        </linearGradient>
                    </defs>
                </svg>
            </span>
            <span class="hd-brand-txt">مزايدة</span>
        </a>
    </div>
</header>

{{-- Auth Shell: Split Screen --}}
<div class="auth-shell">
    {{-- Left Panel: Green gradient with testimonial --}}
    <div class="auth-left">
        <div class="auth-left-content">
            <div class="auth-left-badge">المنصة الوطنية للمزايدات</div>
            <h1 class="auth-left-title">منصة المزايدات الرقمية الأولى في الجزائر</h1>
            <p class="auth-left-desc">انضم إلى آلاف المستخدمين الذين يشاركون في المزايدات العمومية بكل شفافية وأمان عبر منصتنا الرقمية.</p>

            <div class="auth-testimonial">
                <div class="auth-testimonial-stars">
                    @for($i = 0; $i < 5; $i++)
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#D4A843" stroke="#D4A843" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </div>
                <blockquote class="auth-testimonial-txt">"منصة مزايدة غيّرت تجربتنا في المشاركة في المزايدات العمومية. عملية شفافة وسهلة الاستخدام."</blockquote>
                <div class="auth-testimonial-author">
                    <div class="auth-testimonial-avatar">م</div>
                    <div>
                        <div class="auth-testimonial-name">محمد بن عمر</div>
                        <div class="auth-testimonial-role">مدير شركة مقاولات</div>
                    </div>
                </div>
            </div>

            <div class="auth-left-stats">
                <div class="auth-stat">
                    <span class="auth-stat-num">+2,500</span>
                    <span class="auth-stat-label">مزايدة نشطة</span>
                </div>
                <div class="auth-stat">
                    <span class="auth-stat-num">+15,000</span>
                    <span class="auth-stat-label">مستخدم مسجل</span>
                </div>
                <div class="auth-stat">
                    <span class="auth-stat-num">48</span>
                    <span class="auth-stat-label">ولاية</span>
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
