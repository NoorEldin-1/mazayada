@extends('layouts.admin')
@section('title', __('admin.nav_dashboard'))
@section('page-title', __('admin.nav_dashboard'))

@section('content')

{{-- Stat Tiles --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-5">
    <x-ui.stat-tile tone="mint" :label="__('admin.stat_total_users')" :value="$stats['total_users']">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile tone="gold" :label="__('admin.stat_pending_kyc')" :value="$stats['pending_kyc']">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile tone="blue" :label="__('admin.stat_active_auctions')" :value="$stats['active_auctions']">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14.5 17.5 3 3 3-3"/><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile tone="danger" :label="__('admin.stat_total_bids')" :value="$stats['total_bids']">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile tone="gold" :label="__('admin.stat_revenue')" :value="dzd($stats['revenue'])">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile tone="ok" :label="__('admin.stat_active_bidders')" :value="$stats['active_bidders']">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </x-ui.stat-tile>
</div>

{{-- Auction distribution by wilaya (ApexCharts) --}}
@if($wilayaDistribution->isNotEmpty())
@php
    $wilayaChart = [
        'categories' => $wilayaDistribution->pluck('name')->all(),
        'series' => [['name' => __('admin.nav_auctions'), 'data' => $wilayaDistribution->pluck('total')->all()]],
    ];
@endphp
<x-ui.card :title="__('admin.stat_wilaya_distribution')" class="mb-5">
    <div data-chart data-chart-type="bar" data-chart-horizontal="true" data-chart-height="{{ max(240, $wilayaDistribution->count() * 46) }}">
        <div data-chart-target></div>
        <script type="application/json">@json($wilayaChart)</script>
    </div>
</x-ui.card>
@endif

{{-- Recent Auctions --}}
<x-ui.card :padding="false" class="mb-5">
    <x-slot:header>
        <h3 class="text-base font-semibold text-ink">{{ __('admin.recent_auctions') }}</h3>
        <x-ui.btn :href="route('admin.auctions.index')" variant="ghost" size="sm" class="ms-auto">{{ __('common.view_all') }}</x-ui.btn>
    </x-slot:header>
    <div class="overflow-x-auto">
        <table class="ui-table" style="min-width:640px">
            <thead>
                <tr><th>{{ __('admin.th_title') }}</th><th>{{ __('admin.th_entity') }}</th><th>{{ __('admin.th_category') }}</th><th>{{ __('admin.th_price') }}</th><th>{{ __('admin.th_bids') }}</th><th>{{ __('admin.th_status') }}</th></tr>
            </thead>
            <tbody>
                @foreach(\App\Models\Auction::with(['entity','category'])->withCount('bids')->latest()->limit(5)->get() as $auc)
                <tr>
                    <td class="font-semibold text-ink">{{ Str::limit($auc->title_ar, 40) }}</td>
                    <td>{{ $auc->entity?->name ? Str::limit($auc->entity->name, 20) : '—' }}</td>
                    <td>{{ $auc->category?->name ?? '—' }}</td>
                    <td class="num">{{ dzd($auc->opening_price) }}</td>
                    <td class="num">{{ $auc->bids_count }}</td>
                    <td><span class="chip {{ $auc->status->chipClass() }}"><span class="dot"></span>{{ $auc->status->label() }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.card>

{{-- Recent Users --}}
<x-ui.card :padding="false">
    <x-slot:header>
        <h3 class="text-base font-semibold text-ink">{{ __('admin.recent_users') }}</h3>
        <x-ui.btn :href="route('admin.users.index')" variant="ghost" size="sm" class="ms-auto">{{ __('common.view_all') }}</x-ui.btn>
    </x-slot:header>
    <div class="overflow-x-auto">
        <table class="ui-table" style="min-width:640px">
            <thead>
                <tr><th>{{ __('admin.th_name') }}</th><th>{{ __('admin.th_email') }}</th><th>{{ __('admin.th_role') }}</th><th>{{ __('admin.th_kyc') }}</th><th>{{ __('admin.th_registered') }}</th></tr>
            </thead>
            <tbody>
                @foreach(\App\Models\User::latest()->limit(5)->get() as $usr)
                <tr>
                    <td class="font-semibold text-ink">{{ $usr->fullNameAr() }}</td>
                    <td class="lat text-start" dir="ltr">{{ $usr->email }}</td>
                    <td>{{ $usr->role->label() }}</td>
                    <td><span class="chip {{ $usr->kyc_status->chipClass() }}"><span class="dot"></span>{{ $usr->kyc_status->label() }}</span></td>
                    <td class="num">{{ $usr->created_at->format('Y-m-d') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.card>

@endsection
