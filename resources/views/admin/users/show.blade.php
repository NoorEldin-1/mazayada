@extends('layouts.admin')

@section('title', __('admin.users.view_title'))
@section('page-title', __('admin.users.view_title'))

@section('content')

@php
    $bio = $user->biometrics;
    $docs = ['id-front' => __('kyc.doc_id_front'), 'id-back' => __('kyc.doc_id_back'), 'selfie-with-id' => __('kyc.doc_selfie')];
    $income = $user->expected_income ? dzd_html((int) $user->expected_income * 100) : null;
    $identity = [
        'admin.kyc.f_name_ar' => $user->fullNameAr(),
        'kyc.f_first_name_fr' => $user->fullNameFr() ?: null,
        'admin.th_email' => $user->email,
        'admin.users.f_phone' => $user->phone,
        'admin.kyc.f_birth_date' => $user->birth_date?->format('Y-m-d'),
        'kyc.f_father_name' => $user->father_name,
        'kyc.f_mother_fullname' => $user->motherFullName() ?: null,
    ];
    $location = [
        'kyc.f_wilaya' => $user->commune?->wilaya?->name,
        'kyc.f_commune' => $user->commune?->name,
        'kyc.f_full_address' => $user->address,
        'kyc.f_postal_code' => $user->postal_code,
    ];
    $other = [
        'kyc.f_profession' => $user->profession,
        'kyc.f_expected_income' => $income,
        'admin.users.f_nif' => $user->nif,
        'admin.users.f_nis' => $user->nis,
        'kyc.f_rip' => $user->rip,
        'admin.users.f_premium' => $user->premium_until?->format('Y-m-d'),
        'admin.users.f_registered' => $user->created_at?->format('Y-m-d'),
    ];
@endphp

<a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-ink mb-4">
    <span class="rtl:-scale-x-100 inline-block">←</span> {{ __('admin.users.back_to_list') }}
</a>

@if(session('success'))
<div class="mb-5 rounded-xl bg-ok/10 text-ok px-4 py-3 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="mb-5 rounded-xl bg-danger/10 text-danger px-4 py-3 text-sm">{{ $errors->first() }}</div>
@endif

{{-- Header --}}
<x-ui.card class="mb-5">
    <x-slot:header>
        <h3 class="text-base font-semibold text-ink">{{ $user->fullNameAr() }}</h3>
        <div class="ms-auto flex items-center gap-2 flex-wrap">
            <span class="chip chip-info">{{ $user->role->label() }}</span>
            <span class="chip {{ $user->kyc_status->chipClass() }}">{{ $user->kyc_status->label() }}</span>
            @if($user->is_blacklisted)
                <span class="chip chip-danger">{{ __('admin.users.blacklisted') }}</span>
            @else
                <span class="chip {{ $user->account_status->chipClass() }}">{{ $user->account_status->label() }}</span>
            @endif
        </div>
    </x-slot:header>
    <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted">
        <span>NIN: <strong class="text-ink lat" dir="ltr">{{ $user->nin }}</strong></span>
        @if($user->professional_id_no)
            <span>{{ __('admin.users.f_professional_id') }}: <strong class="text-ink lat" dir="ltr">{{ $user->professional_id_no }}</strong></span>
        @endif
        <span>{{ __('admin.users.stat_participations') }}: <strong class="text-ink">{{ $user->participations_count }}</strong></span>
        <span>{{ __('admin.users.stat_bids') }}: <strong class="text-ink">{{ $user->bids_count }}</strong></span>
        <span>{{ __('admin.users.stat_won') }}: <strong class="text-ink">{{ $user->won_auctions_count }}</strong></span>
    </div>
</x-ui.card>

{{-- Identity & contact --}}
<x-ui.card :title="__('admin.users.sec_identity')" :padding="false" class="mb-5">
    <table class="ui-table" style="min-width:0">
        <tbody>
            @foreach($identity as $key => $value)
            <tr><td class="w-60 text-muted">{{ __($key) }}</td><td>{{ $value ?: '—' }}</td></tr>
            @endforeach
        </tbody>
    </table>
</x-ui.card>

{{-- KYC --}}
<x-ui.card class="mb-5" :padding="false">
    <x-slot:header>
        <h3 class="text-base font-semibold text-ink">{{ __('admin.users.sec_kyc') }}</h3>
        @if($user->kyc_status === \App\Enums\KycStatus::UNDER_REVIEW)
            <div class="ms-auto flex items-center gap-2">
                <x-ui.btn variant="primary" size="sm" :href="route('admin.kyc.show', $user)">{{ __('admin.users.view_kyc_review') }}</x-ui.btn>
            </div>
        @endif
    </x-slot:header>
    <div class="p-5 sm:p-6">
        <table class="ui-table" style="min-width:0">
            <tbody>
                <tr><td class="w-60 text-muted">{{ __('admin.th_kyc') }}</td><td><span class="chip {{ $user->kyc_status->chipClass() }}">{{ $user->kyc_status->label() }}</span></td></tr>
                <tr><td class="text-muted">{{ __('admin.kyc.th_submitted_date') }}</td><td>{{ $user->kyc_submitted_at?->format('Y-m-d H:i') ?: '—' }}</td></tr>
                <tr><td class="text-muted">{{ __('admin.users.f_kyc_completed') }}</td><td>{{ $user->kyc_completed_at?->format('Y-m-d H:i') ?: '—' }}</td></tr>
                @if($user->kyc_rejection_reason)
                <tr><td class="text-muted">{{ __('admin.users.f_rejection_reason') }}</td><td>{{ $user->kyc_rejection_reason }}</td></tr>
                @endif
            </tbody>
        </table>

        {{-- Documents --}}
        @if($bio && ($bio->id_front_path || $bio->id_back_path || $bio->selfie_with_id_path))
        <div class="mt-5">
            <div class="text-sm font-semibold text-ink mb-2.5">{{ __('admin.kyc.documents_title') }}</div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach($docs as $type => $label)
                @php $field = str_replace('-', '_', $type) . '_path'; $uploaded = $bio && $bio->$field; @endphp
                <div class="border border-line rounded-xl overflow-hidden">
                    <div class="px-3.5 py-2.5 text-sm font-semibold bg-bg-2">{{ $label }}</div>
                    @if($uploaded)
                        <a href="{{ route('admin.kyc.document', [$user, $type]) }}" target="_blank">
                            <img src="{{ route('admin.kyc.document', [$user, $type]) }}" alt="{{ $label }}" class="w-full block max-h-[200px] object-cover">
                        </a>
                    @else
                        <div class="p-10 text-center text-muted text-sm">{{ __('admin.kyc.no_document') }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</x-ui.card>

{{-- Location --}}
<x-ui.card :title="__('admin.users.sec_location')" :padding="false" class="mb-5">
    <table class="ui-table" style="min-width:0">
        <tbody>
            @foreach($location as $key => $value)
            <tr><td class="w-60 text-muted">{{ __($key) }}</td><td>{{ $value ?: '—' }}</td></tr>
            @endforeach
        </tbody>
    </table>
</x-ui.card>

{{-- Other --}}
<x-ui.card :title="__('admin.users.sec_other')" :padding="false" class="mb-5">
    <table class="ui-table" style="min-width:0">
        <tbody>
            @foreach($other as $key => $value)
            <tr><td class="w-60 text-muted">{{ __($key) }}</td><td>{{ $value ?: '—' }}</td></tr>
            @endforeach
        </tbody>
    </table>
</x-ui.card>

{{-- Account action --}}
<x-ui.card :title="__('admin.users.th_account_status')">
    @if($user->is_blacklisted)
        <div class="mb-3.5 rounded-xl bg-danger/10 text-danger px-4 py-3 text-sm">
            <strong>{{ __('admin.users.blacklisted') }}:</strong> {{ $user->blacklist_reason }}
        </div>
        <form method="POST" action="{{ route('admin.users.unblacklist', $user) }}"
              data-confirm="{{ __('admin.users.confirm_unblacklist_prompt') }}" data-confirm-label="{{ __('admin.users.unblacklist_action') }}">
            @csrf
            <x-ui.btn variant="primary">{{ __('admin.users.unblacklist_action') }}</x-ui.btn>
        </form>
    @else
        <form method="POST" action="{{ route('admin.users.blacklist', $user) }}"
              data-confirm="{{ __('admin.users.confirm_blacklist_prompt') }}" data-confirm-variant="danger" data-confirm-label="{{ __('admin.users.confirm_blacklist') }}">
            @csrf
            <div class="field mb-2.5 max-w-[420px]">
                <label>{{ __('admin.users.blacklist_reason_label') }} <span class="req">*</span></label>
                <input type="text" name="reason" class="input" placeholder="{{ __('admin.users.blacklist_reason_placeholder') }}" required>
            </div>
            <x-ui.btn variant="danger">{{ __('admin.users.confirm_blacklist') }}</x-ui.btn>
        </form>
    @endif
</x-ui.card>

@endsection
