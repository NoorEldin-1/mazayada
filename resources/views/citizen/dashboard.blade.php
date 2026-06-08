@extends('layouts.citizen')

@section('title', __('dashboard.nav_dashboard'))

@section('content')

{{-- Stat tiles --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
    <x-ui.stat-tile tone="mint" :label="__('dashboard.tile_active_auctions')" :value="$activeCount ?? 0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile tone="gold" :label="__('dashboard.tile_won')" :value="$wonCount ?? 0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile tone="blue" :label="__('dashboard.tile_total_participations')" :value="$totalParticipations ?? 0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
    </x-ui.stat-tile>
</div>

{{-- KYC status card --}}
<x-ui.card class="mb-5">
    <x-slot:header>
        <svg class="size-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
        <h3 class="text-base font-semibold text-ink">{{ __('dashboard.kyc_card_title') }}</h3>
        <span class="ms-auto chip {{ auth()->user()->kyc_status->chipClass() }}"><span class="dot"></span>{{ auth()->user()->kyc_status->label() }}</span>
    </x-slot:header>

    @if(!auth()->user()->isKycComplete())
        <p class="text-sm text-ink-2 mb-4">{{ __('dashboard.kyc_incomplete_text') }}</p>
        <x-ui.btn :href="route('citizen.kyc')" variant="primary" size="sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rtl:-scale-x-100"><polyline points="9 18 15 12 9 6"/></svg>
            {{ __('dashboard.kyc_complete_button') }}
        </x-ui.btn>
    @else
        <p class="text-sm text-ok font-medium m-0">{{ __('dashboard.kyc_verified_text') }}</p>
    @endif
</x-ui.card>

{{-- Won auctions --}}
<x-ui.card :padding="false" class="mb-5">
    <x-slot:header>
        <svg class="size-5 text-accent-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <h3 class="text-base font-semibold text-ink">{{ __('dashboard.won_auctions_title') }}</h3>
    </x-slot:header>

    @if(isset($wonAuctions) && $wonAuctions->count())
        <div>
            @foreach($wonAuctions as $auction)
                <a href="{{ route('auctions.show', $auction) }}" class="flex items-center gap-3.5 px-5 sm:px-6 py-3.5 hover:bg-bg-2 transition {{ !$loop->last ? 'border-b border-line' : '' }}">
                    <div class="grid place-items-center size-11 rounded-xl bg-accent/15 text-accent-2 shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-ink truncate">{{ $auction->title_ar }}</div>
                        <div class="text-xs text-muted">{{ $auction->updated_at->format('Y-m-d') }}</div>
                    </div>
                    <div class="num font-bold text-primary text-[15px] shrink-0">{{ dzd($auction->final_price ?? $auction->currentPrice()) }}</div>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center text-muted py-10 px-6 text-sm">{{ __('dashboard.no_won_auctions') }}</div>
    @endif
</x-ui.card>

{{-- Quick actions --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <a href="{{ route('citizen.appeals') }}" class="ui-card p-5 flex items-center gap-3.5 hover:border-primary/40 transition">
        <div class="grid place-items-center size-12 rounded-2xl shrink-0 bg-[color-mix(in_oklab,#8B6DD9_15%,transparent)] text-[#8B6DD9]">
            <svg class="size-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="flex-1 min-w-0">
            <div class="text-[15px] font-semibold text-ink">{{ __('dashboard.quick_appeals_title') }}</div>
            <div class="text-xs text-muted mt-0.5">{{ __('dashboard.quick_appeals_desc') }}</div>
        </div>
        <svg class="size-[18px] text-muted shrink-0 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
    <a href="{{ route('auctions.index') }}" class="ui-card p-5 flex items-center gap-3.5 hover:border-primary/40 transition">
        <div class="grid place-items-center size-12 rounded-2xl shrink-0 bg-primary/12 text-primary">
            <svg class="size-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
        </div>
        <div class="flex-1 min-w-0">
            <div class="text-[15px] font-semibold text-ink">{{ __('dashboard.quick_live_title') }}</div>
            <div class="text-xs text-muted mt-0.5">{{ __('dashboard.quick_live_desc') }}</div>
        </div>
        <svg class="size-[18px] text-muted shrink-0 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
</div>

@endsection
