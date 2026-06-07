@extends('layouts.citizen')
@section('title', __('dashboard.nav_profile'))
@section('content')

<h2 style="font-size:24px;font-weight:700;margin:0 0 20px">{{ __('dashboard.nav_profile') }}</h2>

@if(session('success'))
<div style="background:#E5F3EC;color:#1d6045;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:13px">{{ session('success') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    {{-- Identity Card --}}
    <div class="card">
        <div class="card-h"><h3>{{ __('profile.identity_card') }}</h3></div>
        <div class="card-pad">
            <div style="text-align:center;margin-bottom:20px">
                <div style="width:80px;height:80px;border-radius:20px;background:linear-gradient(135deg,#2D6A4F,#1B4D3E);color:#fff;display:grid;place-items:center;font-weight:700;font-size:28px;margin:0 auto 12px">{{ mb_substr(auth()->user()->first_name_ar, 0, 1) }}</div>
                <h3 style="margin:0 0 4px;font-size:18px">{{ auth()->user()->fullNameAr() }}</h3>
                <p style="color:var(--muted);font-size:13px;margin:0">{{ auth()->user()->email }}</p>
            </div>
            <div style="display:grid;gap:12px">
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line)">
                    <span style="color:var(--muted);font-size:13px">{{ __('profile.nin') }}</span>
                    <span class="num" style="font-size:13px;font-weight:600" dir="ltr">{{ auth()->user()->nin }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line)">
                    <span style="color:var(--muted);font-size:13px">{{ __('profile.phone') }}</span>
                    <span class="num" style="font-size:13px;font-weight:600" dir="ltr">{{ auth()->user()->phone }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line)">
                    <span style="color:var(--muted);font-size:13px">{{ __('profile.role') }}</span>
                    <span class="chip chip-ok"><span class="dot"></span>{{ auth()->user()->role->label() }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line)">
                    <span style="color:var(--muted);font-size:13px">{{ __('profile.kyc_status') }}</span>
                    <span class="chip {{ auth()->user()->kyc_status->chipClass() }}"><span class="dot"></span>{{ auth()->user()->kyc_status->label() }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0">
                    <span style="color:var(--muted);font-size:13px">{{ __('profile.registered_at') }}</span>
                    <span class="num" style="font-size:13px">{{ auth()->user()->created_at->format('Y-m-d') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Profile --}}
    <div class="card">
        <div class="card-h"><h3>{{ __('profile.edit_info') }}</h3></div>
        <div class="card-pad">
            <form action="{{ route('citizen.profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="field" style="margin-bottom:14px">
                    <label>{{ __('profile.address') }}</label>
                    <input class="input" name="address" value="{{ old('address', auth()->user()->address) }}">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
                    <div class="field">
                        <label>{{ __('profile.postal_code') }}</label>
                        <input class="input" name="postal_code" value="{{ old('postal_code', auth()->user()->postal_code) }}" dir="ltr" maxlength="5">
                        @error('postal_code') <small style="color:var(--danger)">{{ $message }}</small> @enderror
                    </div>
                    <div class="field">
                        <label>{{ __('profile.profession') }}</label>
                        <input class="input" name="profession" value="{{ old('profession', auth()->user()->profession) }}">
                    </div>
                </div>
                <div class="field" style="margin-bottom:14px">
                    <label>{{ __('profile.phone') }}</label>
                    <input class="input" name="phone" value="{{ old('phone', auth()->user()->phone) }}" dir="ltr" maxlength="10">
                    @error('phone') <small style="color:var(--danger)">{{ $message }}</small> @enderror
                </div>

                {{-- Account recovery: secret question (spec §8.4) --}}
                <div class="field" style="margin-bottom:14px">
                    <label>{{ __('profile.secret_question') }}</label>
                    <select class="select" name="secret_question">
                        <option value="">{{ __('profile.secret_question_none') }}</option>
                        @foreach((array) __('auth.secret_questions') as $key => $label)
                            <option value="{{ $key }}" {{ old('secret_question', auth()->user()->secret_question) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin-bottom:14px">
                    <label>{{ __('profile.secret_answer') }}</label>
                    <input class="input" name="secret_answer" autocomplete="off"
                           placeholder="{{ auth()->user()->secret_answer ? __('profile.secret_answer_set') : '' }}">
                    <small style="color:var(--muted)">{{ __('profile.secret_answer_hint') }}</small>
                    @error('secret_answer') <small style="color:var(--danger)">{{ $message }}</small> @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-block">{{ __('profile.save_changes') }}</button>
            </form>
        </div>
    </div>
</div>

@endsection
