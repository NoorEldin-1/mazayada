@extends('layouts.admin')

@section('title', __('admin.entities.detail_title'))
@section('page-title', $entity->name)

@section('content')

@php
    $account = $entity->account;
    $accountStatusLabel = $account && $account->account_status ? $account->account_status->label() : null;
    $accountStatusChip = $account && $account->account_status === \App\Enums\AccountStatus::ACTIVE ? 'chip-ok' : 'chip-danger';

    $identity = [
        __('admin.entities.f_name_internal') => $entity->getRawOriginal('name'),
        __('admin.entities.f_name_ar') => $entity->name_ar,
        __('admin.entities.f_name_fr') => $entity->name_fr ?: '—',
        __('admin.entities.f_type') => $entity->type?->label() ?? '—',
    ];

    $location = [
        __('admin.entities.f_wilaya') => optional($entity->wilaya)->name ?? '—',
        __('admin.entities.f_commune') => optional($entity->commune)->name ?? '—',
        __('admin.entities.f_address') => $entity->address ?: '—',
        __('admin.entities.f_phone') => $entity->phone ?: '—',
    ];
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex items-center gap-3">
        <span class="chip chip-info">{{ $entity->type?->label() }}</span>
        <span class="chip {{ $entity->is_active ? 'chip-ok' : 'chip-muted' }}">
            {{ $entity->is_active ? __('common.active') : __('common.inactive') }}
        </span>
    </div>
    <div class="flex items-center gap-2">
        <x-ui.btn variant="ghost" size="sm" :href="route('admin.entities.edit', $entity)">{{ __('common.edit') }}</x-ui.btn>
        <x-ui.btn variant="ghost" size="sm" :href="route('admin.entities.index')">{{ __('admin.entities.back_to_list') }}</x-ui.btn>
    </div>
</div>

{{-- ===== Stats ===== --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <x-ui.card>
        <div class="text-xs text-muted mb-1">{{ __('admin.entities.col_auctions') }}</div>
        <div class="text-ink font-semibold num" style="font-size:1.5rem">{{ $entity->auctions_count }}</div>
    </x-ui.card>
    <x-ui.card>
        <div class="text-xs text-muted mb-1">{{ __('admin.entities.col_staff') }}</div>
        <div class="text-ink font-semibold num" style="font-size:1.5rem">{{ $entity->entity_users_count }}</div>
    </x-ui.card>
</div>

{{-- ===== Identity ===== --}}
<x-ui.card :title="__('admin.entities.sec_identity')" class="mb-6">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
        @foreach($identity as $label => $value)
            <div>
                <div class="text-xs text-muted mb-1">{{ $label }}</div>
                <div class="text-ink font-medium">{{ $value }}</div>
            </div>
        @endforeach
    </div>
</x-ui.card>

{{-- ===== Location ===== --}}
<x-ui.card :title="__('admin.entities.sec_location')" class="mb-6">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
        @foreach($location as $label => $value)
            <div>
                <div class="text-xs text-muted mb-1">{{ $label }}</div>
                <div class="text-ink font-medium">{{ $value }}</div>
            </div>
        @endforeach
    </div>
</x-ui.card>

{{-- ===== Account (institutional login) ===== --}}
<x-ui.card :title="__('admin.entities.sec_account')" class="mb-6">
    @if($account)
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
            <div>
                <div class="text-xs text-muted mb-1">{{ __('admin.entities.f_email') }}</div>
                <div class="text-ink font-medium" style="direction:ltr;text-align:start">{{ $account->email }}</div>
            </div>
            <div>
                <div class="text-xs text-muted mb-1">{{ __('admin.entities.f_account_status') }}</div>
                <div><span class="chip {{ $accountStatusChip }}">{{ $accountStatusLabel ?? '—' }}</span></div>
            </div>
            <div>
                <div class="text-xs text-muted mb-1">{{ __('admin.entities.f_registered') }}</div>
                <div class="text-ink font-medium num">{{ optional($account->created_at)->format('Y-m-d H:i') ?? '—' }}</div>
            </div>
        </div>
    @else
        <p class="text-muted text-sm">{{ __('admin.entities.no_login_note') }}</p>
    @endif
</x-ui.card>

@endsection
