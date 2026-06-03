@extends('layouts.auth')

@section('title', __('auth.otp_title'))

@section('content')
<div class="auth-form">
    <h1>{{ __('auth.otp_title') }}</h1>
    <p>{{ __('auth.otp_subtitle') }}</p>
</div>

@if(session('status'))
    <div style="background:#E5F3EC;color:#1d6045;padding:12px 16px;border-radius:11px;font-size:13px;margin-bottom:16px">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('verify-otp') }}" class="auth-form">
    @csrf
    <input type="hidden" name="user_id" value="{{ old('user_id', session('user_id')) }}">

    <div class="grp">
        {{-- OTP --}}
        <div class="field">
            <label for="otp">{{ __('auth.otp_label') }} <span class="req">*</span></label>
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
                    style="text-align:center;font-family:'Inter';font-size:24px;font-weight:700;letter-spacing:12px"
                    value="{{ old('otp') }}"
                    required
                    autofocus
                >
            </div>
            @if($errors->first('otp'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('otp') }}</span>
            @endif
        </div>
    </div>

    {{-- Submit --}}
    <button type="submit" class="btn btn-primary btn-block btn-lg">
        {{ __('auth.otp_button') }}
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </button>

    {{-- Resend --}}
    <div style="text-align:center;margin-top:22px">
        <button type="submit" name="resend" value="1" formnovalidate style="color:var(--primary);font-weight:600;font-size:13px;background:none;border:none;cursor:pointer;text-decoration:underline">
            {{ __('auth.resend') }}
        </button>
        <p style="font-size:12px;color:var(--muted);margin-top:8px">{{ __('auth.resend_hint') }}</p>
    </div>
</form>
@endsection
