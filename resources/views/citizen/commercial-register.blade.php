@extends('layouts.citizen')
@section('title', __('commercial-register.page_title'))
@section('content')

@php
    use App\Enums\CommercialRegisterStatus;
    $status = $register?->status;
    $isPending  = $status === CommercialRegisterStatus::PENDING;
    $isApproved = $status === CommercialRegisterStatus::APPROVED;
    $isRejected = $status === CommercialRegisterStatus::REJECTED;
    // Editable when there is no record yet, or the last decision was a rejection.
    $canSubmit = $register === null || $register->canSubmit();
    $hasRegisterDoc = (bool) $register?->register_document_path;
    $hasTaxCardDoc  = (bool) $register?->tax_card_document_path;
    $ro = $canSubmit ? '' : 'disabled';
@endphp

<x-ui.page-header :title="__('commercial-register.page_title')" :subtitle="__('commercial-register.page_subtitle')" />

{{-- Status banner --}}
@if($isPending)
<div class="bg-info/10 text-info flex gap-3 items-center rounded-2xl px-5 py-4 mb-5 text-sm">
    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    <div>
        <strong>{{ __('commercial-register.banner_pending_title') }}</strong>
        <div style="margin-top:2px">{{ __('commercial-register.banner_pending_text', ['date' => $register->submitted_at?->format('Y-m-d H:i')]) }}</div>
    </div>
</div>
@elseif($isApproved)
<div class="bg-ok/10 text-ok flex gap-3 items-center rounded-2xl px-5 py-4 mb-5 text-sm">
    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <div>
        <strong>{{ __('commercial-register.banner_approved_title') }}</strong>
        <div style="margin-top:2px">{{ __('commercial-register.banner_approved_text') }}</div>
        @if($register->isExpired())
            <div style="margin-top:4px" class="text-danger">{{ __('commercial-register.banner_expired_text') }}</div>
        @endif
    </div>
</div>
@elseif($isRejected)
<div class="bg-danger/10 text-danger flex gap-3 items-start rounded-2xl px-5 py-4 mb-5 text-sm">
    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div>
        <strong>{{ __('commercial-register.banner_rejected_title') }}</strong>
        @if($register->rejection_reason)
            <div style="margin-top:4px">{{ __('commercial-register.banner_rejected_reason') }} <strong>{{ $register->rejection_reason }}</strong></div>
        @endif
        <div style="margin-top:4px">{{ __('commercial-register.banner_rejected_hint') }}</div>
    </div>
</div>
@else
<div class="bg-accent-soft text-accent-2 flex gap-3 items-center rounded-2xl px-5 py-4 mb-5 text-sm">
    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7h-9M14 17H5M17 3v4M7 21v-4"/><rect x="3" y="7" width="4" height="10" rx="1"/><rect x="17" y="7" width="4" height="10" rx="1"/></svg>
    <div>
        <strong>{{ __('commercial-register.banner_none_title') }}</strong>
        <div style="margin-top:2px">{{ __('commercial-register.banner_none_text') }}</div>
    </div>
</div>
@endif

@if($errors->any())
<div class="mb-5 rounded-xl px-4 py-3 text-sm bg-danger/10 text-danger flex items-center gap-2">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    {{ $errors->first() }}
</div>
@endif

@if(session('success'))
<div class="mb-5 rounded-xl px-4 py-3 text-sm bg-ok/10 text-ok">{{ session('success') }}</div>
@endif

<form action="{{ route('citizen.commercial-register.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- Register data --}}
    <x-ui.card :title="__('commercial-register.sec_data_title')" class="mb-5">
        <div class="grid sm:grid-cols-2 gap-4 mb-4">
            <div class="field">
                <label>{{ __('commercial-register.f_company_name') }} <span class="req">*</span></label>
                <input class="input" name="company_name" value="{{ old('company_name', $register?->company_name) }}" {{ $ro }} required>
            </div>
            <div class="field">
                <label>{{ __('commercial-register.f_register_number') }} <span class="req">*</span></label>
                <input class="input" name="register_number" value="{{ old('register_number', $register?->register_number) }}" dir="ltr" {{ $ro }} required>
            </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-4 mb-4">
            <div class="field">
                <label>{{ __('commercial-register.f_tax_number') }} <span class="req">*</span></label>
                <input class="input" name="tax_number" value="{{ old('tax_number', $register?->tax_number) }}" dir="ltr" {{ $ro }} required>
            </div>
            <div class="field">
                <label>{{ __('commercial-register.f_activity_type') }} <span class="req">*</span></label>
                <input class="input" name="activity_type" value="{{ old('activity_type', $register?->activity_type) }}" {{ $ro }} required>
            </div>
        </div>
        <div class="field" style="max-width:260px">
            <label>{{ __('commercial-register.f_expiry_date') }} <span class="req">*</span></label>
            <input class="input" type="date" name="expiry_date" value="{{ old('expiry_date', $register?->expiry_date?->format('Y-m-d')) }}" dir="ltr" {{ $ro }} required>
        </div>
    </x-ui.card>

    {{-- Documents --}}
    <x-ui.card :title="__('commercial-register.sec_docs_title')" class="mb-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Register scan --}}
            <div class="field">
                <label>{{ __('commercial-register.f_register_document') }} @unless($hasRegisterDoc)<span class="req">*</span>@endunless</label>
                @if($hasRegisterDoc)
                    <a href="{{ route('citizen.commercial-register.document', 'register') }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-primary font-semibold mb-2">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        {{ __('commercial-register.view_current_file') }}
                    </a>
                @endif
                @if($canSubmit)
                    <input class="input" type="file" name="register_document" accept=".pdf,image/jpeg,image/png">
                    <small class="text-muted text-xs mt-1">{{ __('commercial-register.upload_hint') }}</small>
                @endif
            </div>
            {{-- Tax card scan --}}
            <div class="field">
                <label>{{ __('commercial-register.f_tax_card_document') }} @unless($hasTaxCardDoc)<span class="req">*</span>@endunless</label>
                @if($hasTaxCardDoc)
                    <a href="{{ route('citizen.commercial-register.document', 'tax-card') }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-primary font-semibold mb-2">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        {{ __('commercial-register.view_current_file') }}
                    </a>
                @endif
                @if($canSubmit)
                    <input class="input" type="file" name="tax_card_document" accept=".pdf,image/jpeg,image/png">
                    <small class="text-muted text-xs mt-1">{{ __('commercial-register.upload_hint') }}</small>
                @endif
            </div>
        </div>
    </x-ui.card>

    {{-- Submit --}}
    <x-ui.card class="mb-5">
        @if($canSubmit)
            <x-ui.btn variant="primary" size="lg" class="w-full">{{ __('commercial-register.submit') }}</x-ui.btn>
        @elseif($isPending)
            <div class="bg-info/10 text-info rounded-xl px-4 py-3.5 text-sm text-center">{{ __('commercial-register.banner_pending_title') }}</div>
        @elseif($isApproved)
            <div class="bg-ok/10 text-ok rounded-xl px-4 py-3.5 text-sm text-center">{{ __('commercial-register.banner_approved_text') }}</div>
        @endif
    </x-ui.card>
</form>

@endsection
