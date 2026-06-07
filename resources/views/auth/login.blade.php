@extends('layouts.auth')

@section('title', __('auth.login_title'))

@section('content')
<div class="auth-form">
    <h1>{{ __('auth.login_title') }}</h1>
    <p>{{ __('auth.login_subtitle') }}</p>
</div>

<form method="POST" action="{{ route('login') }}" class="auth-form">
    @csrf

    <div class="grp">
        {{-- NIN or Email --}}
        <div class="field">
            <label for="login">{{ __('auth.login_id_label') }} <span class="req">*</span></label>
            <div class="input-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <input
                    type="text"
                    id="login"
                    name="nin_or_email"
                    class="input has-ic"
                    dir="ltr"
                    placeholder="{{ __('auth.login_id_placeholder') }}"
                    value="{{ old('nin_or_email') }}"
                    required
                    autofocus
                >
            </div>
            @if($errors->first('nin_or_email'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('nin_or_email') }}</span>
            @endif
        </div>

        {{-- Password --}}
        <div class="field">
            <label for="password">{{ __('auth.password_label') }} <span class="req">*</span></label>
            <div class="input-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="input has-ic"
                    dir="ltr"
                    placeholder="********"
                    required
                >
                <button type="button" onclick="togglePassword(this)" style="position:absolute;inset-inline-end:12px;top:50%;transform:translateY(-50%);color:var(--muted);cursor:pointer" tabindex="-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            @if($errors->first('password'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('password') }}</span>
            @endif
        </div>
    </div>

    {{-- Forgot password / lost-email recovery --}}
    <div style="display:flex;justify-content:space-between;gap:12px;margin-bottom:18px">
        <a href="{{ route('password.recover') }}" style="font-size:13px;color:var(--muted);font-weight:600">{{ __('auth.lost_email_recover') }}</a>
        <a href="{{ route('password.reset') }}" style="font-size:13px;color:var(--primary);font-weight:600">{{ __('auth.forgot_password') }}</a>
    </div>

    {{-- General errors --}}
    @if(session('success'))
        <div style="background:#E5F3EC;color:#1d6045;padding:12px 16px;border-radius:11px;font-size:13px;margin-bottom:16px">
            {{ session('success') }}
        </div>
    @endif

    {{-- Submit --}}
    <button type="submit" class="btn btn-primary btn-block btn-lg">
        {{ __('auth.login_button') }}
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </button>

    {{-- Register link --}}
    <div class="footer-link">
        {{ __('auth.no_account') }} <a href="{{ route('register') }}">{{ __('auth.create_account_link') }}</a>
    </div>
</form>

<script>
function togglePassword(btn) {
    const input = btn.closest('.input-wrap').querySelector('input');
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
    }
}
</script>
@endsection
