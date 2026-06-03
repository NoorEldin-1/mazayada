@extends('layouts.auth')

@section('title', __('auth.register_title'))

@section('content')
<form method="POST" action="{{ route('register') }}" class="auth-form">
    @csrf

    <div class="grp">
        {{-- NIN --}}
        <div class="field">
            <label for="nin">{{ __('auth.nin_label') }} <span class="req">*</span></label>
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
                >
            </div>
            @if($errors->first('nin'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('nin') }}</span>
            @endif
        </div>

        {{-- First name + Last name (Arabic) --}}
        <div class="row2">
            <div class="field">
                <label for="first_name_ar">{{ __('auth.first_name_ar_label') }} <span class="req">*</span></label>
                <input
                    type="text"
                    id="first_name_ar"
                    name="first_name_ar"
                    class="input"
                    placeholder="{{ __('auth.first_name_placeholder') }}"
                    value="{{ old('first_name_ar') }}"
                    required
                >
                @if($errors->first('first_name_ar'))
                    <span style="color:var(--danger);font-size:12px">{{ $errors->first('first_name_ar') }}</span>
                @endif
            </div>
            <div class="field">
                <label for="last_name_ar">{{ __('auth.last_name_ar_label') }} <span class="req">*</span></label>
                <input
                    type="text"
                    id="last_name_ar"
                    name="last_name_ar"
                    class="input"
                    placeholder="{{ __('auth.last_name_placeholder') }}"
                    value="{{ old('last_name_ar') }}"
                    required
                >
                @if($errors->first('last_name_ar'))
                    <span style="color:var(--danger);font-size:12px">{{ $errors->first('last_name_ar') }}</span>
                @endif
            </div>
        </div>

        {{-- Phone + Email --}}
        <div class="row2">
            <div class="field">
                <label for="phone">{{ __('auth.phone_label') }} <span class="req">*</span></label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    class="input"
                    dir="ltr"
                    placeholder="05XX XX XX XX"
                    value="{{ old('phone') }}"
                    required
                >
                @if($errors->first('phone'))
                    <span style="color:var(--danger);font-size:12px">{{ $errors->first('phone') }}</span>
                @endif
            </div>
            <div class="field">
                <label for="email">{{ __('auth.email_label') }} <span class="req">*</span></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="input"
                    dir="ltr"
                    placeholder="example@email.com"
                    value="{{ old('email') }}"
                    required
                >
                @if($errors->first('email'))
                    <span style="color:var(--danger);font-size:12px">{{ $errors->first('email') }}</span>
                @endif
            </div>
        </div>

        {{-- Birth date --}}
        <div class="field">
            <label for="birth_date">{{ __('auth.birth_date_label') }} <span class="req">*</span></label>
            <input
                type="date"
                id="birth_date"
                name="birth_date"
                class="input"
                dir="ltr"
                value="{{ old('birth_date') }}"
                required
            >
            @if($errors->first('birth_date'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('birth_date') }}</span>
            @endif
        </div>

        {{-- Password + Confirmation --}}
        <div class="row2">
            <div class="field">
                <label for="password">{{ __('auth.password_label') }} <span class="req">*</span></label>
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
            <div class="field">
                <label for="password_confirmation">{{ __('auth.password_confirm_label') }} <span class="req">*</span></label>
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
    </div>

    {{-- Terms --}}
    <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:18px">
        <input type="checkbox" id="terms" name="terms" value="1" style="margin-top:4px" {{ old('terms') ? 'checked' : '' }} required>
        <label for="terms" class="legal" style="margin:0">
            {!! __('auth.terms_agree', [
                'terms' => '<a href="#">'.e(__('auth.terms_link')).'</a>',
                'privacy' => '<a href="#">'.e(__('auth.privacy_link')).'</a>',
            ]) !!}
        </label>
    </div>
    @if($errors->first('terms'))
        <span style="color:var(--danger);font-size:12px;display:block;margin-bottom:12px">{{ $errors->first('terms') }}</span>
    @endif

    {{-- Submit --}}
    <button type="submit" class="btn btn-primary btn-block btn-lg">
        {{ __('auth.register_button') }}
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
    </button>

    {{-- Login link --}}
    <div class="footer-link">
        {{ __('auth.have_account') }} <a href="{{ route('login') }}">{{ __('auth.login_link') }}</a>
    </div>
</form>
@endsection
