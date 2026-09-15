@extends('layouts.admin')

@section('title', __('email_recovery.admin.show_title'))
@section('page-title', __('email_recovery.admin.show_title'))

@section('content')

@php
    $user = $request->user;
    $bio = $user?->biometrics;
    $birthMatches = $user?->birth_date?->toDateString() === $request->birth_date?->toDateString();
    $phoneMatches = preg_replace('/\D/', '', (string) $user?->phone) === preg_replace('/\D/', '', (string) $request->phone);
    $kycDocs = [
        'selfie-with-id' => ['selfie_with_id_path', __('email_recovery.admin.kyc_selfie')],
        'id-front' => ['id_front_path', __('email_recovery.admin.kyc_id_front')],
    ];
@endphp

<a href="{{ route('admin.email-recovery.index') }}" class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-ink mb-4">
    <span class="rtl:-scale-x-100 inline-block">←</span> {{ __('email_recovery.admin.back') }}
</a>

{{-- Header --}}
<x-ui.card class="mb-5">
    <x-slot:header>
        <h3 class="text-base font-semibold text-ink">{{ $user?->fullNameAr() ?: '—' }}</h3>
        <div class="ms-auto flex items-center gap-2">
            <span class="chip {{ $request->status->chipClass() }}"><span class="dot"></span>{{ $request->status->label() }}</span>
        </div>
    </x-slot:header>
    <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted">
        <span>NIN: <strong class="text-ink lat" dir="ltr">{{ $request->nin }}</strong></span>
        <span>{{ __('email_recovery.admin.f_submitted_at') }}: <strong class="text-ink num">{{ $request->submitted_at?->format('Y-m-d H:i') }}</strong></span>
        @if($user)
            <span>{{ __('email_recovery.admin.f_kyc_status') }}: <span class="chip {{ $user->kyc_status?->chipClass() }}">{{ $user->kyc_status?->label() }}</span></span>
        @endif
    </div>
</x-ui.card>

{{-- Email change --}}
<x-ui.card :title="__('email_recovery.admin.change_title')" :padding="false" class="mb-5">
    <table class="ui-table" style="min-width:0">
        <tbody>
            <tr>
                <td class="w-60 text-muted">{{ __('email_recovery.admin.f_old_email') }}</td>
                <td dir="ltr" style="text-align:start">{{ $request->old_email ?: '—' }}</td>
            </tr>
            <tr>
                <td class="w-60 text-muted">{{ __('email_recovery.admin.f_new_email') }}</td>
                <td dir="ltr" style="text-align:start"><strong>{{ $request->new_email }}</strong></td>
            </tr>
        </tbody>
    </table>
</x-ui.card>

{{-- Identity check: what the citizen typed vs. what the account holds --}}
<x-ui.card :title="__('email_recovery.admin.identity_title')" :padding="false" class="mb-5">
    <table class="ui-table" style="min-width:0">
        <thead>
            <tr>
                <th></th>
                <th>{{ __('email_recovery.admin.col_request') }}</th>
                <th>{{ __('email_recovery.admin.col_account') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-muted">{{ __('email_recovery.admin.f_birth_date') }}</td>
                <td class="num">{{ $request->birth_date?->format('Y-m-d') }}</td>
                <td class="num">{{ $user?->birth_date?->format('Y-m-d') ?? '—' }}</td>
                <td><span class="chip {{ $birthMatches ? 'chip-ok' : 'chip-danger' }}">{{ $birthMatches ? __('email_recovery.admin.matches') : __('email_recovery.admin.differs') }}</span></td>
            </tr>
            <tr>
                <td class="text-muted">{{ __('email_recovery.admin.f_phone') }}</td>
                <td class="num" dir="ltr" style="text-align:start">{{ $request->phone }}</td>
                <td class="num" dir="ltr" style="text-align:start">{{ $user?->phone ?? '—' }}</td>
                <td><span class="chip {{ $phoneMatches ? 'chip-ok' : 'chip-danger' }}">{{ $phoneMatches ? __('email_recovery.admin.matches') : __('email_recovery.admin.differs') }}</span></td>
            </tr>
            <tr>
                <td class="text-muted">{{ __('email_recovery.admin.f_ip') }}</td>
                <td class="num" dir="ltr" style="text-align:start" colspan="3">{{ $request->ip_address ?? '—' }}</td>
            </tr>
        </tbody>
    </table>
</x-ui.card>

{{-- Photos: submitted selfie next to the KYC documents on file --}}
<x-ui.card :title="__('email_recovery.admin.documents_title')" class="mb-5">
    <p class="text-sm text-muted mb-3">{{ __('email_recovery.admin.compare_hint') }}</p>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="border border-primary rounded-xl overflow-hidden">
            <div class="px-3.5 py-2.5 text-sm font-semibold bg-bg-2">{{ __('email_recovery.admin.submitted_selfie') }}</div>
            <a href="{{ route('admin.email-recovery.selfie', $request) }}" target="_blank">
                <img src="{{ route('admin.email-recovery.selfie', $request) }}" alt="{{ __('email_recovery.admin.submitted_selfie') }}" class="w-full block max-h-[260px] object-cover">
            </a>
        </div>
        @foreach($kycDocs as $type => [$field, $label])
            <div class="border border-line rounded-xl overflow-hidden">
                <div class="px-3.5 py-2.5 text-sm font-semibold bg-bg-2">{{ $label }}</div>
                @if($user && $bio?->$field)
                    <a href="{{ route('admin.kyc.document', [$user, $type]) }}" target="_blank">
                        <img src="{{ route('admin.kyc.document', [$user, $type]) }}" alt="{{ $label }}" class="w-full block max-h-[260px] object-cover">
                    </a>
                @else
                    <div class="p-10 text-center text-muted text-sm">{{ __('email_recovery.admin.no_kyc_doc') }}</div>
                @endif
            </div>
        @endforeach
    </div>
</x-ui.card>

{{-- Decision / outcome --}}
@if($request->isOpen())
<x-ui.card :title="__('email_recovery.admin.decision_title')">
    <div class="flex flex-col gap-[18px]">
        @if($request->status === \App\Enums\EmailRecoveryStatus::PENDING)
            <form method="POST" action="{{ route('admin.email-recovery.start-review', $request) }}">
                @csrf
                <x-ui.btn variant="ghost">{{ __('email_recovery.admin.start_review') }}</x-ui.btn>
                <small class="text-muted text-xs ms-2">{{ __('email_recovery.admin.start_review_hint') }}</small>
            </form>
        @endif

        @can('kyc.approve')
        <form method="POST" action="{{ route('admin.email-recovery.approve', $request) }}" data-confirm="{{ __('email_recovery.admin.confirm_approve') }}" data-confirm-label="{{ __('email_recovery.admin.approve') }}">
            @csrf
            <x-ui.btn variant="primary">{{ __('email_recovery.admin.approve') }}</x-ui.btn>
        </form>
        @endcan

        @can('kyc.reject')
        <form method="POST" action="{{ route('admin.email-recovery.reject', $request) }}">
            @csrf
            <div class="field mb-2.5">
                <label>{{ __('email_recovery.admin.reject_reason') }} <span class="req">*</span></label>
                <input type="text" name="reason" class="input" maxlength="500" placeholder="{{ __('email_recovery.admin.reject_placeholder') }}" required>
            </div>
            <x-ui.btn variant="danger">{{ __('email_recovery.admin.reject') }}</x-ui.btn>
        </form>
        @endcan
    </div>
</x-ui.card>
@else
<x-ui.card :title="__('email_recovery.admin.outcome_title')" :padding="false">
    <table class="ui-table" style="min-width:0">
        <tbody>
            <tr>
                <td class="w-60 text-muted">{{ __('common.status') }}</td>
                <td><span class="chip {{ $request->status->chipClass() }}">{{ $request->status->label() }}</span></td>
            </tr>
            <tr>
                <td class="w-60 text-muted">{{ __('email_recovery.admin.f_reviewed_by') }}</td>
                <td>{{ $request->reviewedBy?->fullNameAr() ?: '—' }}</td>
            </tr>
            <tr>
                <td class="w-60 text-muted">{{ __('email_recovery.admin.f_reviewed_at') }}</td>
                <td class="num">{{ $request->reviewed_at?->format('Y-m-d H:i') ?? '—' }}</td>
            </tr>
            @if($request->rejection_reason)
            <tr>
                <td class="w-60 text-muted">{{ __('email_recovery.admin.f_reason') }}</td>
                <td>{{ $request->rejection_reason }}</td>
            </tr>
            @endif
        </tbody>
    </table>
</x-ui.card>
@endif

@endsection
