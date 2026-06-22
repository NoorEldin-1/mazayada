@extends('layouts.admin')

@section('title', __('admin.entity_staff.detail_title'))
@section('page-title', $member->full_name ?? optional($member->user)->fullNameAr())

@section('content')

@php
    $u = $member->user;
    $roleLabel = \App\Enums\UserRole::tryFrom($member->role)?->label() ?? $member->role;
    $accountStatusLabel = $u && $u->account_status ? $u->account_status->label() : '—';
    $accountStatusChip = $u && $u->account_status === \App\Enums\AccountStatus::ACTIVE ? 'chip-ok' : 'chip-danger';

    $assignment = [
        __('admin.entity_staff.col_entity') => optional($member->entity)->name ?? '—',
        __('admin.entity_staff.f_role') => $roleLabel,
        __('admin.entity_staff.f_username') => $member->username,
    ];

    $identity = [
        __('admin.entity_staff.f_full_name') => ($u ? $u->fullNameAr() : '') ?: ($member->full_name ?? '—'),
        __('admin.entity_staff.f_nin') => ($u ? $u->nin : null) ?: '—',
        __('admin.entity_staff.f_professional_id') => ($u ? $u->professional_id_no : null) ?: '—',
        __('admin.entity_staff.f_email') => ($u ? $u->email : null) ?: '—',
        __('admin.entity_staff.f_phone') => ($u ? $u->phone : null) ?: '—',
        __('admin.entity_staff.f_birth_date') => optional($u ? $u->birth_date : null)->format('Y-m-d') ?? '—',
    ];
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex items-center gap-3">
        <span class="chip chip-info">{{ $roleLabel }}</span>
        <span class="chip {{ $member->is_active ? 'chip-ok' : 'chip-muted' }}">
            {{ $member->is_active ? __('common.active') : __('common.inactive') }}
        </span>
    </div>
    <div class="flex items-center gap-2">
        <x-ui.btn variant="ghost" size="sm" :href="route('admin.entity-staff.edit', $member)">{{ __('admin.entity_staff.reset_password') }}</x-ui.btn>
        <x-ui.btn variant="ghost" size="sm" :href="route('admin.entity-staff.index')">{{ __('admin.entity_staff.back_to_list') }}</x-ui.btn>
    </div>
</div>

{{-- ===== Assignment ===== --}}
<x-ui.card :title="__('admin.entity_staff.sec_assignment')" class="mb-6">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
        @foreach($assignment as $label => $value)
            <div>
                <div class="text-xs text-muted mb-1">{{ $label }}</div>
                <div class="text-ink font-medium">{{ $value }}</div>
            </div>
        @endforeach
    </div>
</x-ui.card>

{{-- ===== Identity ===== --}}
<x-ui.card :title="__('admin.entity_staff.sec_identity')" class="mb-6">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
        @foreach($identity as $label => $value)
            <div>
                <div class="text-xs text-muted mb-1">{{ $label }}</div>
                <div class="text-ink font-medium" style="direction:ltr;text-align:start">{{ $value }}</div>
            </div>
        @endforeach
    </div>
</x-ui.card>

{{-- ===== Account ===== --}}
<x-ui.card :title="__('admin.entity_staff.sec_account')" class="mb-6">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
        <div>
            <div class="text-xs text-muted mb-1">{{ __('admin.entity_staff.f_account_status') }}</div>
            <div><span class="chip {{ $accountStatusChip }}">{{ $accountStatusLabel }}</span></div>
        </div>
        <div>
            <div class="text-xs text-muted mb-1">{{ __('admin.entity_staff.col_status') }}</div>
            <div>
                <span class="chip {{ $member->is_active ? 'chip-ok' : 'chip-muted' }}">
                    {{ $member->is_active ? __('common.active') : __('common.inactive') }}
                </span>
            </div>
        </div>
        <div>
            <div class="text-xs text-muted mb-1">{{ __('admin.entity_staff.f_registered') }}</div>
            <div class="text-ink font-medium num">{{ optional($member->created_at)->format('Y-m-d H:i') ?? '—' }}</div>
        </div>
    </div>
</x-ui.card>

@endsection
