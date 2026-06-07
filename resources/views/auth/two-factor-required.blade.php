@extends('layouts.auth')

@section('title', __('auth.two_factor_title'))

@section('content')
@php($user = auth()->user())

<div class="auth-form">
    <h1>{{ __('auth.two_factor_title') }}</h1>
    <p>{{ __('auth.two_factor_intro') }}</p>
</div>

@if(session('status'))
    <div style="background:#E5F3EC;color:#1d6045;padding:12px 16px;border-radius:11px;font-size:13px;margin-bottom:16px">
        {{ session('status') }}
    </div>
@endif

@if($user->two_factor_confirmed_at)
    {{-- Already set up --}}
    <div class="auth-form" style="text-align:center">
        <p style="color:#1d6045;font-weight:600">{{ __('auth.two_factor_enabled') }}</p>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-block btn-lg" style="margin-top:12px">{{ __('auth.two_factor_go_dashboard') }}</a>
    </div>
@elseif($user->two_factor_secret)
    {{-- Enabled but not yet confirmed — show QR + confirm form --}}
    <div class="auth-form" style="text-align:center">
        <p>{{ __('auth.two_factor_scan') }}</p>
        <div style="display:flex;justify-content:center;margin:14px 0">
            {!! $user->twoFactorQrCodeSvg() !!}
        </div>
    </div>

    <form method="POST" action="/user/confirmed-two-factor-authentication" class="auth-form">
        @csrf
        <div class="field">
            <label for="code">{{ __('auth.two_factor_code_label') }} <span class="req">*</span></label>
            <input type="text" id="code" name="code" class="input" dir="ltr" inputmode="numeric" maxlength="6"
                   placeholder="000000" style="text-align:center;letter-spacing:8px;font-weight:700" required autofocus>
            @if($errors->first('code'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('code') }}</span>
            @endif
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">{{ __('auth.two_factor_confirm') }}</button>
    </form>

    <form method="POST" action="/user/two-factor-authentication" style="text-align:center;margin-top:14px">
        @csrf @method('DELETE')
        <button type="submit" style="color:var(--danger);font-weight:600;font-size:13px;background:none;border:none;cursor:pointer">{{ __('auth.two_factor_cancel') }}</button>
    </form>
@else
    {{-- Not started --}}
    <form method="POST" action="/user/two-factor-authentication" class="auth-form">
        @csrf
        <p style="font-size:13px;color:var(--muted);margin-bottom:14px">{{ __('auth.two_factor_enable_hint') }}</p>
        <button type="submit" class="btn btn-primary btn-block btn-lg">{{ __('auth.two_factor_enable') }}</button>
    </form>
@endif

<div class="footer-link">
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" style="color:var(--primary);font-weight:600;font-size:13px;background:none;border:none;cursor:pointer">{{ __('auth.logout') }}</button>
    </form>
</div>
@endsection
