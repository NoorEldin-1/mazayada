@extends('layouts.admin')

@section('title', __('admin.kyc.review_title'))
@section('page-title', __('admin.kyc.review_title'))

@section('content')

@php
    $docs = ['id-front' => __('kyc.doc_id_front'), 'id-back' => __('kyc.doc_id_back'), 'selfie-with-id' => __('kyc.doc_selfie')];
    $bio = $user->biometrics;
    $rows = [
        'kyc.f_first_name_fr' => $user->first_name_fr,
        'kyc.f_last_name_fr' => $user->last_name_fr,
        'admin.kyc.f_name_ar' => $user->fullNameAr(),
        'kyc.f_father_name' => $user->father_name,
        'kyc.f_mother_fullname' => $user->motherFullName() ?: null,
        'admin.kyc.f_birth_date' => $user->birth_date?->format('Y-m-d'),
        'kyc.f_wilaya' => $user->commune?->wilaya?->name,
        'kyc.f_commune' => $user->commune?->name,
        'kyc.f_full_address' => $user->address,
        'kyc.f_postal_code' => $user->postal_code,
        'kyc.f_profession' => $user->profession,
        'kyc.f_expected_income' => $user->expected_income ? number_format($user->expected_income, 0, ',', ' ').' '.__('common.currency') : null,
        'kyc.f_rip' => $user->rip,
    ];
@endphp

<a href="{{ route('admin.kyc.index') }}" class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-ink mb-4">
    <span class="rtl:-scale-x-100 inline-block">←</span> {{ __('admin.kyc.back_to_queue') }}
</a>

@if($errors->any())
<div class="mb-5 rounded-xl bg-danger/10 text-danger px-4 py-3 text-sm">{{ $errors->first() }}</div>
@endif

{{-- Identity header --}}
<x-ui.card class="mb-5">
    <x-slot:header>
        <h3 class="text-base font-semibold text-ink">{{ $user->fullNameAr() }} <span class="text-muted font-normal">— {{ $user->fullNameFr() }}</span></h3>
        <div class="ms-auto flex items-center gap-2">
            <span class="chip {{ $user->kyc_status->chipClass() }}"><span class="dot"></span>{{ $user->kyc_status->label() }}</span>
        </div>
    </x-slot:header>
    <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted">
        <span>NIN: <strong class="text-ink lat" dir="ltr">{{ $user->nin }}</strong></span>
        <span>{{ __('admin.kyc.th_email_short') }}: <strong class="text-ink lat" dir="ltr">{{ $user->email }}</strong></span>
        <span>{{ __('admin.kyc.th_submitted_date') }}: <strong class="text-ink">{{ $user->kyc_submitted_at?->format('Y-m-d H:i') }}</strong></span>
    </div>
</x-ui.card>

{{-- Documents --}}
<x-ui.card :title="__('admin.kyc.documents_title')" class="mb-5">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @foreach($docs as $type => $label)
        @php $field = str_replace('-', '_', $type) . '_path'; $uploaded = $bio && $bio->$field; @endphp
        <div class="border border-line rounded-xl overflow-hidden">
            <div class="px-3.5 py-2.5 text-sm font-semibold bg-bg-2">{{ $label }}</div>
            @if($uploaded)
                <a href="{{ route('admin.kyc.document', [$user, $type]) }}" target="_blank">
                    <img src="{{ route('admin.kyc.document', [$user, $type]) }}" alt="{{ $label }}" class="w-full block max-h-[220px] object-cover">
                </a>
            @else
                <div class="p-10 text-center text-muted text-sm">{{ __('admin.kyc.no_document') }}</div>
            @endif
        </div>
        @endforeach
    </div>
</x-ui.card>

{{-- Personal info --}}
<x-ui.card :title="__('admin.kyc.personal_info_title')" :padding="false" class="mb-5">
    <table class="ui-table" style="min-width:0">
        <tbody>
            @foreach($rows as $key => $value)
            <tr>
                <td class="w-60 text-muted">{{ __($key) }}</td>
                <td>{{ $value ?: '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</x-ui.card>

{{-- Decision --}}
@if($user->kyc_status === \App\Enums\KycStatus::UNDER_REVIEW)
<x-ui.card :title="__('admin.kyc.decision_title')">
    <div class="flex flex-col gap-[18px]">
        {{-- Approve --}}
        <form method="POST" action="{{ route('admin.kyc.approve', $user) }}" data-confirm="{{ __('admin.kyc.confirm_approve') }}" data-confirm-label="{{ __('admin.kyc.approve') }}">
            @csrf
            <x-ui.btn variant="primary">{{ __('admin.kyc.approve') }}</x-ui.btn>
        </form>
        {{-- Reject --}}
        <form method="POST" action="{{ route('admin.kyc.reject', $user) }}">
            @csrf
            <div class="field mb-2.5">
                <label>{{ __('admin.kyc.reject_reason_label') }} <span class="req">*</span></label>
                <input type="text" name="reason" class="input" placeholder="{{ __('admin.kyc.reject_reason_placeholder') }}" required>
            </div>
            <x-ui.btn variant="danger">{{ __('admin.kyc.reject') }}</x-ui.btn>
        </form>
    </div>
</x-ui.card>
@endif

@endsection
