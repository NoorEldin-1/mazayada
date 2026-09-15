@extends('layouts.auth')

@section('title', __('email_recovery.form.title'))

@section('content')
@php
    $submitted = session('email_recovery_submitted');
    $lookupDone = $lookupDone ?? false;
    $lookup = $lookup ?? null;
@endphp

<div class="auth-form">
    <h1>{{ __('email_recovery.form.title') }}</h1>
    <p>{{ __('email_recovery.form.subtitle') }}</p>
</div>

@if($submitted)
    {{-- ===== Confirmation ===== --}}
    <div class="auth-form">
        <div style="background:#E5F3EC;color:#1d6045;padding:16px 18px;border-radius:12px;font-size:13.5px;line-height:1.8;margin-bottom:16px">
            <strong style="display:block;font-size:15px;margin-bottom:4px">{{ __('email_recovery.form.submitted_title') }}</strong>
            {{ __('email_recovery.form.submitted_text') }}
        </div>
        <div class="footer-link">
            <a href="{{ route('login') }}">{{ __('auth.back_to_login') }}</a>
        </div>
    </div>
@else
    {{-- ===== How it works ===== --}}
    <div class="auth-form" style="margin-bottom:6px">
        <div style="background:#F2F4F8;border-radius:12px;padding:12px 16px;font-size:12.5px;line-height:1.9;color:var(--ink-2)">
            <strong style="display:block;color:var(--ink);margin-bottom:2px">{{ __('email_recovery.form.steps_title') }}</strong>
            1. {{ __('email_recovery.form.step_1') }}<br>
            2. {{ __('email_recovery.form.step_2') }}<br>
            3. {{ __('email_recovery.form.step_3') }}
        </div>
    </div>

    {{-- ===== Request form ===== --}}
    <form method="POST" action="{{ route('email-recovery.store') }}" enctype="multipart/form-data" class="auth-form">
        @csrf

        <div class="grp">
            <div class="field">
                <label for="nin">{{ __('email_recovery.form.nin') }} <span class="req">*</span></label>
                <input type="text" id="nin" name="nin" class="input" dir="ltr" maxlength="18" inputmode="numeric" value="{{ old('nin') }}" required autofocus>
                @error('nin') <span style="color:var(--danger);font-size:12px">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label for="birth_date">{{ __('email_recovery.form.birth_date') }} <span class="req">*</span></label>
                <input type="date" id="birth_date" name="birth_date" class="input" dir="ltr" value="{{ old('birth_date') }}" required>
                @error('birth_date') <span style="color:var(--danger);font-size:12px">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label for="phone">{{ __('email_recovery.form.phone') }} <span class="req">*</span></label>
                <input type="tel" id="phone" name="phone" class="input" dir="ltr" maxlength="10" inputmode="numeric" placeholder="05XXXXXXXX" value="{{ old('phone') }}" required>
                @error('phone') <span style="color:var(--danger);font-size:12px">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label for="new_email">{{ __('email_recovery.form.new_email') }} <span class="req">*</span></label>
                <input type="email" id="new_email" name="new_email" class="input" dir="ltr" placeholder="example@email.com" value="{{ old('new_email') }}" required>
                <small style="color:var(--muted);font-size:11.5px">{{ __('email_recovery.form.new_email_hint') }}</small>
                @error('new_email') <span style="color:var(--danger);font-size:12px;display:block">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label for="selfie_with_id">{{ __('email_recovery.form.selfie') }} <span class="req">*</span></label>
                <input type="file" id="selfie_with_id" name="selfie_with_id" class="input" accept="image/jpeg,image/png" required>
                <small style="color:var(--muted);font-size:11.5px">{{ __('email_recovery.form.selfie_hint') }}</small>
                @error('selfie_with_id') <span style="color:var(--danger);font-size:12px;display:block">{{ $message }}</span> @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">{{ __('email_recovery.form.submit') }}</button>

        <div class="footer-link">
            <a href="{{ route('password.recover') }}">{{ __('email_recovery.form.secret_link') }}</a>
            <span style="margin:0 8px">·</span>
            <a href="{{ route('login') }}">{{ __('auth.back_to_login') }}</a>
        </div>
    </form>
@endif

{{-- ===== Status lookup ===== --}}
<form method="POST" action="{{ route('email-recovery.status') }}" class="auth-form" style="margin-top:22px;padding-top:18px;border-top:1px solid var(--line)">
    @csrf
    <h2 style="font-size:15px;font-weight:700;margin:0 0 4px">{{ __('email_recovery.lookup.title') }}</h2>
    <p style="font-size:12.5px;color:var(--muted);margin:0 0 10px">{{ __('email_recovery.lookup.subtitle') }}</p>

    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <input type="text" name="status_nin" class="input" dir="ltr" maxlength="18" inputmode="numeric" value="{{ old('status_nin', request('status_nin')) }}" placeholder="{{ __('email_recovery.form.nin') }}" style="flex:1 1 180px" required>
        <button type="submit" class="btn btn-outline">{{ __('email_recovery.lookup.check') }}</button>
    </div>
    @error('status_nin') <span style="color:var(--danger);font-size:12px">{{ $message }}</span> @enderror

    @if($lookupDone)
        @if($lookup)
            <div style="margin-top:12px;border:1px solid var(--line);border-radius:12px;padding:12px 14px;font-size:13px;line-height:1.9">
                <div><span style="color:var(--muted)">{{ __('email_recovery.lookup.status') }}:</span> <strong>{{ $lookup->status->label() }}</strong></div>
                <div><span style="color:var(--muted)">{{ __('email_recovery.lookup.new_email') }}:</span> <span dir="ltr">{{ mask_email($lookup->new_email) }}</span></div>
                <div><span style="color:var(--muted)">{{ __('email_recovery.lookup.submitted_at') }}:</span> <span class="num">{{ $lookup->submitted_at?->format('Y-m-d H:i') }}</span></div>
                @if($lookup->reviewed_at)
                    <div><span style="color:var(--muted)">{{ __('email_recovery.lookup.reviewed_at') }}:</span> <span class="num">{{ $lookup->reviewed_at->format('Y-m-d H:i') }}</span></div>
                @endif
                @if($lookup->status === \App\Enums\EmailRecoveryStatus::REJECTED && $lookup->rejection_reason)
                    <div style="color:var(--danger)"><span>{{ __('email_recovery.lookup.reason') }}:</span> {{ $lookup->rejection_reason }}</div>
                @endif
            </div>
        @else
            <div style="margin-top:12px;font-size:13px;color:var(--muted)">{{ __('email_recovery.lookup.none') }}</div>
        @endif
    @endif
</form>
@endsection
