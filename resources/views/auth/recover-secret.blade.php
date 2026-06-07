@extends('layouts.auth')

@section('title', __('auth.recover_title'))

@section('content')
@php($step2 = session('recover_step') == 2)

<div class="auth-form">
    <h1>{{ __('auth.recover_title') }}</h1>
    <p>{{ $step2 ? __('auth.recover_subtitle_answer') : __('auth.recover_subtitle_identify') }}</p>
</div>

@if($step2)
{{-- ===== Step 2: answer the secret question + set a new password ===== --}}
<form method="POST" action="{{ route('password.recover') }}" class="auth-form">
    @csrf
    <input type="hidden" name="step" value="2">
    <input type="hidden" name="nin" value="{{ old('nin', session('recover_nin')) }}">
    <input type="hidden" name="email" value="{{ old('email', session('recover_email')) }}">

    <div class="grp">
        <div class="field">
            <label>{{ __('auth.recover_your_question') }}</label>
            <p style="font-weight:600;margin:4px 0 10px">
                {{ __('auth.secret_questions.'.session('recover_question')) }}
            </p>
        </div>

        <div class="field">
            <label for="secret_answer">{{ __('auth.recover_answer_label') }} <span class="req">*</span></label>
            <input type="text" id="secret_answer" name="secret_answer" class="input" required autofocus>
            @if($errors->first('secret_answer'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('secret_answer') }}</span>
            @endif
        </div>

        <div class="field">
            <label for="password">{{ __('auth.new_password_label') }} <span class="req">*</span></label>
            <input type="password" id="password" name="password" class="input" dir="ltr" placeholder="********" required>
            @if($errors->first('password'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('password') }}</span>
            @endif
        </div>

        <div class="field">
            <label for="password_confirmation">{{ __('auth.password_confirm_label') }} <span class="req">*</span></label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="input" dir="ltr" placeholder="********" required>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg">{{ __('auth.change_password_button') }}</button>

    <div class="footer-link">
        <a href="{{ route('login') }}">{{ __('auth.back_to_login') }}</a>
    </div>
</form>

@else
{{-- ===== Step 1: identify the account ===== --}}
<form method="POST" action="{{ route('password.recover') }}" class="auth-form">
    @csrf
    <input type="hidden" name="step" value="1">

    <div class="grp">
        <div class="field">
            <label for="nin">{{ __('auth.nin_label') }} <span class="req">*</span></label>
            <input type="text" id="nin" name="nin" class="input" dir="ltr" maxlength="18" inputmode="numeric" value="{{ old('nin') }}" required autofocus>
            @if($errors->first('nin'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('nin') }}</span>
            @endif
        </div>

        <div class="field">
            <label for="email">{{ __('auth.email_label') }} <span class="req">*</span></label>
            <input type="email" id="email" name="email" class="input" dir="ltr" placeholder="example@email.com" value="{{ old('email') }}" required>
            @if($errors->first('email'))
                <span style="color:var(--danger);font-size:12px">{{ $errors->first('email') }}</span>
            @endif
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg">{{ __('auth.recover_continue') }}</button>

    <div class="footer-link">
        <a href="{{ route('password.reset') }}">{{ __('auth.recover_use_email_instead') }}</a>
        <span style="margin:0 8px">·</span>
        <a href="{{ route('login') }}">{{ __('auth.back_to_login') }}</a>
    </div>
</form>
@endif
@endsection
