@extends('layouts.citizen')
@section('title', __('dashboard.nav_profile'))
@section('content')

<x-ui.page-header :title="__('dashboard.nav_profile')" />

@if(session('success'))
<div class="mb-5 rounded-xl bg-ok/10 text-ok px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="mb-5 rounded-xl bg-danger/10 text-danger px-4 py-3 text-sm">
    <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="grid lg:grid-cols-2 gap-5">
    {{-- Identity Card --}}
    <x-ui.card :title="__('profile.identity_card')">
        <div class="text-center mb-5">
            <div class="w-20 h-20 rounded-[20px] text-white grid place-items-center font-bold text-[28px] mx-auto mb-3" style="background:linear-gradient(135deg,#2D6A4F,#1B4D3E)">{{ mb_substr($user->name, 0, 1) }}</div>
            <h3 class="m-0 mb-1 text-lg text-ink">{{ $user->name }}</h3>
            <p class="text-muted text-[13px] m-0">{{ $user->email }}</p>
        </div>
        <div class="grid gap-3">
            <div class="flex justify-between py-2.5 border-b border-line">
                <span class="text-muted text-sm">{{ __('profile.nin') }}</span>
                <span class="num text-[13px] font-semibold" dir="ltr">{{ $user->nin }}</span>
            </div>
            <div class="flex justify-between py-2.5 border-b border-line">
                <span class="text-muted text-sm">{{ __('profile.phone') }}</span>
                <span class="num text-[13px] font-semibold" dir="ltr">{{ $user->phone }}</span>
            </div>
            <div class="flex justify-between py-2.5 border-b border-line">
                <span class="text-muted text-sm">{{ __('profile.role') }}</span>
                <span class="chip chip-ok"><span class="dot"></span>{{ $user->role->label() }}</span>
            </div>
            <div class="flex justify-between py-2.5 border-b border-line">
                <span class="text-muted text-sm">{{ __('profile.kyc_status') }}</span>
                <span class="chip {{ $user->kyc_status->chipClass() }}"><span class="dot"></span>{{ $user->kyc_status->label() }}</span>
            </div>
            <div class="flex justify-between py-2.5">
                <span class="text-muted text-sm">{{ __('profile.registered_at') }}</span>
                <span class="num text-[13px]">{{ $user->created_at->format('Y-m-d') }}</span>
            </div>
        </div>
    </x-ui.card>

    {{-- Edit Profile --}}
    <x-ui.card :title="__('profile.edit_info')">
        <form action="{{ route('citizen.profile.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="field" style="margin-bottom:14px">
                <label>{{ __('profile.address') }}</label>
                <input class="input" name="address" value="{{ old('address', $user->address) }}">
            </div>
            <div class="grid sm:grid-cols-2 gap-3.5 mb-3.5">
                <div class="field">
                    <label>{{ __('profile.postal_code') }}</label>
                    <input class="input" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" dir="ltr" maxlength="5">
                    @error('postal_code') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label>{{ __('profile.profession') }}</label>
                    <input class="input" name="profession" value="{{ old('profession', $user->profession) }}">
                </div>
            </div>
            <div class="field" style="margin-bottom:14px">
                <label>{{ __('profile.phone') }}</label>
                <input class="input" name="phone" value="{{ old('phone', $user->phone) }}" dir="ltr" maxlength="10">
                @error('phone') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            {{-- Account recovery: secret question (spec §8.4) --}}
            <div class="field" style="margin-bottom:14px">
                <label>{{ __('profile.secret_question') }}</label>
                <select class="select" name="secret_question">
                    <option value="">{{ __('profile.secret_question_none') }}</option>
                    @foreach((array) __('auth.secret_questions') as $key => $label)
                        <option value="{{ $key }}" {{ old('secret_question', $user->secret_question) === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="margin-bottom:14px">
                <label>{{ __('profile.secret_answer') }}</label>
                <input class="input" name="secret_answer" autocomplete="off"
                       placeholder="{{ $user->secret_answer ? __('profile.secret_answer_set') : '' }}">
                <small class="text-muted text-xs">{{ __('profile.secret_answer_hint') }}</small>
                @error('secret_answer') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <x-ui.btn variant="primary" class="w-full">{{ __('profile.save_changes') }}</x-ui.btn>
        </form>
    </x-ui.card>
</div>

@endsection
