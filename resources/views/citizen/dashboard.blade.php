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
                        <div class="text-sm font-semibold text-ink truncate">{{ $auction->localizedTitle() }}</div>
                        <div class="text-xs text-muted num">{{ ($auction->closed_at ?? $auction->updated_at)->format('Y-m-d') }}</div>
                    </div>
                    <div class="num font-bold text-primary text-[15px] shrink-0"><x-money :centimes="$auction->final_price ?? $auction->currentPrice()" /></div>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center text-muted py-10 px-6 text-sm">{{ __('dashboard.no_won_auctions') }}</div>
    @endif
</x-ui.card>

{{-- Recent notifications --}}
<x-ui.card :padding="false" class="mb-5">
    <x-slot:header>
        <svg class="size-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <h3 class="text-base font-semibold text-ink">{{ __('dashboard.recent_notifications_title') }}</h3>
        <a href="{{ route('citizen.notifications') }}" class="ms-auto text-xs font-semibold text-primary hover:underline shrink-0">{{ __('dashboard.recent_notifications_view_all') }}</a>
    </x-slot:header>

    @if(isset($recentNotifications) && $recentNotifications->count())
        <div>
            @foreach($recentNotifications as $notif)
                <a href="{{ $notif->action_url ?: route('citizen.notifications') }}"
                   class="flex items-start gap-3.5 px-5 sm:px-6 py-3.5 hover:bg-bg-2 transition {{ !$notif->is_read ? 'bg-primary/5' : '' }} {{ !$loop->last ? 'border-b border-line' : '' }}">
                    <div class="grid place-items-center size-10 rounded-xl shrink-0 {{ !$notif->is_read ? 'bg-primary/10 text-primary' : 'bg-bg-2 text-muted' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold truncate {{ !$notif->is_read ? 'text-ink' : 'text-ink-2' }}">{{ $notif->title }}</span>
                            @if(!$notif->is_read)
                                <span class="size-2 rounded-full bg-primary shrink-0"></span>
                            @endif
                            <span class="ms-auto text-[11px] text-muted whitespace-nowrap shrink-0">{{ $notif->created_at->diffForHumans() }}</span>
                        </div>
                        @if($notif->body)
                            <p class="m-0 mt-0.5 text-xs text-muted leading-relaxed line-clamp-2">{{ $notif->body }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center text-muted py-10 px-6 text-sm">{{ __('dashboard.no_recent_notifications') }}</div>
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

{{-- Forced verification modal — auto-opens for not-verified (PENDING) / rejected users. --}}
<x-kyc-verify-modal />

@endsection
