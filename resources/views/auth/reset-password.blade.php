@extends('layouts.auth')

@section('title', 'استعادة كلمة المرور')

@section('auth-heading')
    <h1>استعادة كلمة المرور</h1>
    @if(session('reset_step') == 2)
        <p>أدخل رمز التحقق وكلمة المرور الجديدة.</p>
    @else
        <p>أدخل رقم التعريف الوطني والبريد الإلكتروني لاستلام رمز التحقق.</p>
    @endif
@endsection

@section('auth-form')
@if(session('reset_step') == 2)
{{-- ===== Step 2: OTP + New Password ===== --}}
<form method="POST" action="{{ route('password.reset') }}" class="auth-form">
    @csrf
    <input type="hidden" name="step" value="2">
    <input type="hidden" name="nin" value="{{ session('reset_nin') }}">
    <input type="hidden" name="email" value="{{ session('reset_email') }}">

    <div class="grp">
        {{-- OTP --}}
        <div class="field">
            <label for="otp">رمز التحقق <span class="req">*</span></label>
            <div class="input-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><circle cx="12" cy="16" r="1"/></svg>
                <input
                    type="text"
                    id="otp"
                    name="otp"
                    class="input has-ic"
                    dir="ltr"
                    maxlength="6"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    placeholder="000000"
                    style="text-align:center;font-family:'Inter';font-size:20px;font-weight:700;letter-spacing:10px"
                    value="{{ old('otp') }}"
                    required
                    autofocus
                >
            </div>
            @if($errors->first('otp'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('otp') }}</span>
            @endif
        </div>

        {{-- New Password --}}
        <div class="field">
            <label for="password">كلمة المرور الجديدة <span class="req">*</span></label>
            <input
                type="password"
                id="password"
                name="password"
                class="input"
                dir="ltr"
                placeholder="********"
                required
            >
            @if($errors->first('password'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('password') }}</span>
            @endif
        </div>

        {{-- Confirm Password --}}
        <div class="field">
            <label for="password_confirmation">تأكيد كلمة المرور <span class="req">*</span></label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                class="input"
                dir="ltr"
                placeholder="********"
                required
            >
        </div>
    </div>

    {{-- General errors --}}
    @if($errors->has('general'))
        <div style="background:#FBE2E0;color:#8E2F2A;padding:12px 16px;border-radius:11px;font-size:13px;margin-bottom:16px">
            {{ $errors->first('general') }}
        </div>
    @endif

    {{-- Submit --}}
    <button type="submit" class="btn btn-primary btn-block btn-lg">
        تغيير كلمة المرور
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </button>

    <div class="footer-link">
        <a href="{{ route('login') }}">العودة لتسجيل الدخول</a>
    </div>
</form>

@else
{{-- ===== Step 1: NIN + Email ===== --}}
<form method="POST" action="{{ route('password.reset') }}" class="auth-form">
    @csrf
    <input type="hidden" name="step" value="1">

    <div class="grp">
        {{-- NIN --}}
        <div class="field">
            <label for="nin">رقم التعريف الوطني (NIN) <span class="req">*</span></label>
            <div class="input-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                <input
                    type="text"
                    id="nin"
                    name="nin"
                    class="input has-ic"
                    dir="ltr"
                    maxlength="18"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    placeholder="000000000000000000"
                    value="{{ old('nin') }}"
                    required
                    autofocus
                >
            </div>
            @if($errors->first('nin'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('nin') }}</span>
            @endif
        </div>

        {{-- Email --}}
        <div class="field">
            <label for="email">البريد الإلكتروني <span class="req">*</span></label>
            <div class="input-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="input has-ic"
                    dir="ltr"
                    placeholder="example@email.com"
                    value="{{ old('email') }}"
                    required
                >
            </div>
            @if($errors->first('email'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('email') }}</span>
            @endif
        </div>
    </div>

    {{-- General errors --}}
    @if($errors->has('general'))
        <div style="background:#FBE2E0;color:#8E2F2A;padding:12px 16px;border-radius:11px;font-size:13px;margin-bottom:16px">
            {{ $errors->first('general') }}
        </div>
    @endif

    {{-- Success message --}}
    @if(session('status'))
        <div style="background:#E5F3EC;color:#1d6045;padding:12px 16px;border-radius:11px;font-size:13px;margin-bottom:16px">
            {{ session('status') }}
        </div>
    @endif

    {{-- Submit --}}
    <button type="submit" class="btn btn-primary btn-block btn-lg">
        إرسال رمز التحقق
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
    </button>

    <div class="footer-link">
        <a href="{{ route('login') }}">العودة لتسجيل الدخول</a>
    </div>
</form>
@endif
@endsection
