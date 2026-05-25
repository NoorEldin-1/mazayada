<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة الإدارة') — مزايدة</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/mazayada.css">
    <style>
        .adm{display:flex;min-height:100vh}
        .adm-side{width:260px;background:var(--primary);color:#fff;display:flex;flex-direction:column;position:fixed;inset-block:0;inset-inline-start:0;z-index:40}
        .adm-side .logo{padding:22px 20px;display:flex;align-items:center;gap:11px;border-bottom:1px solid rgba(255,255,255,.1)}
        .adm-side .logo .mark{width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,.15);display:grid;place-items:center}
        .adm-side .logo .mark svg{width:20px;height:20px}
        .adm-side .logo .txt{font-size:18px;font-weight:700}
        .adm-side .logo .sub{font-size:11px;opacity:.6;margin-top:2px}
        .adm-nav{flex:1;padding:14px 10px;display:flex;flex-direction:column;gap:3px;overflow-y:auto}
        .adm-nav a,.adm-nav button{display:flex;align-items:center;gap:11px;padding:11px 14px;border-radius:11px;font-size:14px;font-weight:500;color:rgba(255,255,255,.7);text-decoration:none;width:100%;text-align:start;transition:all .15s}
        .adm-nav a:hover,.adm-nav button:hover{background:rgba(255,255,255,.08);color:#fff}
        .adm-nav a.on{background:rgba(255,255,255,.14);color:#fff;font-weight:600}
        .adm-nav a svg,.adm-nav button svg{width:20px;height:20px;flex-shrink:0;opacity:.7}
        .adm-nav a.on svg{opacity:1}
        .adm-nav .sep{height:1px;background:rgba(255,255,255,.1);margin:8px 6px}
        .adm-nav .create{background:var(--accent);color:#3a2a08;font-weight:600;border-radius:12px;margin-top:4px}
        .adm-nav .create:hover{background:#E6BB52}
        .adm-nav .create svg{opacity:1}
        .adm-foot{padding:16px 14px;border-top:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:10px}
        .adm-foot .av{width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.15);display:grid;place-items:center;font-weight:700;font-size:14px;flex-shrink:0}
        .adm-foot .nm{font-size:13px;font-weight:600}
        .adm-foot .rl{font-size:11px;opacity:.6}
        .adm-main{margin-inline-start:260px;flex:1;background:var(--bg);min-height:100vh}
        .adm-top{background:var(--surface);border-bottom:1px solid var(--line);padding:16px 28px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:30}
        .adm-top h1{font-size:22px;font-weight:700;margin:0}
        .adm-top .acts{display:flex;align-items:center;gap:10px}
        .adm-top .acts a,.adm-top .acts button{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;color:var(--ink-2);background:var(--bg);border:1px solid var(--line)}
        .adm-top .acts a:hover,.adm-top .acts button:hover{background:#F2F4F8}
        .adm-body{padding:28px}
        @media(max-width:1100px){.adm-side{width:72px;overflow:hidden}.adm-side .logo .txt,.adm-side .logo .sub,.adm-nav span,.adm-foot .nm,.adm-foot .rl{display:none}.adm-main{margin-inline-start:72px}.adm-nav a,.adm-nav button{justify-content:center;padding:11px}}
    </style>
    @stack('styles')
</head>
<body>
<div class="adm">
    {{-- Sidebar --}}
    <aside class="adm-side" id="admSide">
        <div class="logo">
            <div class="mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 17.5 3 3 3-3"/><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/></svg></div>
            <div><div class="txt">مزايدة</div><div class="sub">لوحة الإدارة</div></div>
        </div>
        <nav class="adm-nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                <span>لوحة التحكم</span>
            </a>
            <a href="{{ route('admin.auctions.index') }}" class="{{ request()->routeIs('admin.auctions.index','admin.auctions.edit') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14.5 17.5 3 3 3-3"/><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/></svg>
                <span>المزايدات</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>المستخدمون</span>
            </a>
            <a href="{{ route('admin.kyc.index') }}" class="{{ request()->routeIs('admin.kyc.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>التحقق (KYC)</span>
            </a>
            <a href="{{ route('admin.appeals.index') }}" class="{{ request()->routeIs('admin.appeals.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>الطعون</span>
            </a>
            <a href="{{ route('admin.audit-logs') }}" class="{{ request()->routeIs('admin.audit-logs') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>سجل المراجعة</span>
            </a>
            <div class="sep"></div>
            <a href="{{ route('admin.auctions.create') }}" class="create">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>إنشاء مزايدة</span>
            </a>
        </nav>
        <div class="adm-foot">
            <div class="av">{{ auth()->check() ? mb_substr(auth()->user()->first_name_ar, 0, 1) : '؟' }}</div>
            <div>
                <div class="nm">{{ auth()->check() ? auth()->user()->fullNameAr() : '' }}</div>
                <div class="rl">{{ auth()->check() ? auth()->user()->role->label() : '' }}</div>
            </div>
        </div>
    </aside>

    {{-- Mobile drawer backdrop --}}
    <div class="adm-backdrop" id="admBackdrop" onclick="document.getElementById('admSide').classList.remove('open');this.classList.remove('on')"></div>

    {{-- Main --}}
    <div class="adm-main">
        <header class="adm-top">
            <button type="button" class="adm-burger" aria-label="القائمة" onclick="const s=document.getElementById('admSide'),b=document.getElementById('admBackdrop');s.classList.toggle('open');b.classList.toggle('on')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <h1>@yield('page-title', 'لوحة التحكم')</h1>
            <div class="acts">
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button type="submit" title="تسجيل الخروج">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </button>
                </form>
            </div>
        </header>
        <div class="adm-body">
            @yield('content')
        </div>
    </div>
</div>
@stack('scripts')
</body>
</html>
