@extends('layouts.admin')

@section('title', __('admin.commercial_registers.review_title'))
@section('page-title', __('admin.commercial_registers.review_title'))

@section('content')

@php
    use Illuminate\Support\Str;
    $user = $register->user;
    $docs = [
        'register' => ['label' => __('commercial-register.f_register_document'), 'path' => $register->register_document_path],
        'tax-card' => ['label' => __('commercial-register.f_tax_card_document'), 'path' => $register->tax_card_document_path],
    ];
    $rows = [
        'commercial-register.f_company_name' => $register->company_name,
        'commercial-register.f_register_number' => $register->register_number,
        'commercial-register.f_tax_number' => $register->tax_number,
        'commercial-register.f_activity_type' => $register->activity_type,
        'commercial-register.f_expiry_date' => $register->expiry_date?->format('Y-m-d'),
    ];
    $isImage = fn (?string $path) => $path && Str::endsWith(Str::lower($path), ['.jpg', '.jpeg', '.png']);
@endphp

<a href="{{ route('admin.commercial-registers.index') }}" class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-ink mb-4">
    <span class="rtl:-scale-x-100 inline-block">←</span> {{ __('admin.commercial_registers.back_to_queue') }}
</a>

@if($errors->any())
<div class="mb-5 rounded-xl bg-danger/10 text-danger px-4 py-3 text-sm">{{ $errors->first() }}</div>
@endif

{{-- Header --}}
<x-ui.card class="mb-5">
    <x-slot:header>
        <h3 class="text-base font-semibold text-ink">{{ $register->company_name }}</h3>
        <div class="ms-auto flex items-center gap-2">
            <span class="chip {{ $register->status->chipClass() }}"><span class="dot"></span>{{ $register->status->label() }}</span>
        </div>
    </x-slot:header>
    <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted">
        <span>{{ __('admin.th_name') }}: <strong class="text-ink">{{ $user?->fullNameAr() }}</strong></span>
        <span>{{ __('admin.commercial_registers.th_email_short') }}: <strong class="text-ink lat" dir="ltr">{{ $user?->email }}</strong></span>
        <span>{{ __('admin.commercial_registers.th_submitted_date') }}: <strong class="text-ink">{{ $register->submitted_at?->format('Y-m-d H:i') }}</strong></span>
    </div>
</x-ui.card>

{{-- Data --}}
<x-ui.card :title="__('commercial-register.sec_data_title')" :padding="false" class="mb-5">
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

{{-- Documents --}}
<x-ui.card :title="__('commercial-register.sec_docs_title')" class="mb-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach($docs as $type => $doc)
        <div class="border border-line rounded-xl overflow-hidden">
            <div class="px-3.5 py-2.5 text-sm font-semibold bg-bg-2">{{ $doc['label'] }}</div>
            @if($doc['path'])
                @if($isImage($doc['path']))
                    <a href="{{ route('admin.commercial-registers.document', [$register, $type]) }}" target="_blank">
                        <img src="{{ route('admin.commercial-registers.document', [$register, $type]) }}" alt="{{ $doc['label'] }}" class="w-full block max-h-[220px] object-cover">
                    </a>
                @else
                    <a href="{{ route('admin.commercial-registers.document', [$register, $type]) }}" target="_blank" class="flex items-center justify-center gap-2 p-10 text-primary font-semibold text-sm">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        {{ __('commercial-register.open_pdf') }}
                    </a>
                @endif
            @else
                <div class="p-10 text-center text-muted text-sm">{{ __('admin.commercial_registers.no_document') }}</div>
            @endif
        </div>
        @endforeach
    </div>
</x-ui.card>

{{-- Decision --}}
@if($register->status === \App\Enums\CommercialRegisterStatus::PENDING)
<x-ui.card :title="__('admin.commercial_registers.decision_title')">
    <div class="flex flex-col gap-[18px]">
        {{-- Approve --}}
        <form method="POST" action="{{ route('admin.commercial-registers.approve', $register) }}" data-confirm="{{ __('admin.commercial_registers.confirm_approve') }}" data-confirm-label="{{ __('admin.commercial_registers.approve') }}">
            @csrf
            <x-ui.btn variant="primary">{{ __('admin.commercial_registers.approve') }}</x-ui.btn>
        </form>
        {{-- Reject --}}
        <form method="POST" action="{{ route('admin.commercial-registers.reject', $register) }}">
            @csrf
            <div class="field mb-2.5">
                <label>{{ __('admin.commercial_registers.reject_reason_label') }} <span class="req">*</span></label>
                <input type="text" name="reason" class="input" placeholder="{{ __('admin.commercial_registers.reject_reason_placeholder') }}" required>
            </div>
            <x-ui.btn variant="danger">{{ __('admin.commercial_registers.reject') }}</x-ui.btn>
        </form>
    </div>
</x-ui.card>
@endif

@endsection
