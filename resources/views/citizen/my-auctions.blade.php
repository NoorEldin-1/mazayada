@extends('layouts.citizen')
@section('title', __('dashboard.nav_my_auctions'))
@section('content')

<x-ui.page-header :title="__('dashboard.nav_my_auctions')" />

<div class="flex flex-wrap gap-2 mb-6">
    @foreach(['active' => __('dashboard.tab_active'), 'won' => __('dashboard.tab_won'), 'lost' => __('dashboard.tab_lost'), 'upcoming' => __('dashboard.tab_upcoming')] as $key => $label)
    <x-ui.btn :variant="$tab === $key ? 'primary' : 'ghost'" size="sm" :href="'?tab='.$key">
        {{ $label }}
        @if(($counts[$key] ?? 0) > 0)
        <span class="num inline-grid place-items-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold leading-none {{ $tab === $key ? 'bg-white/20 text-white' : 'bg-bg-2 text-muted' }}">{{ $counts[$key] }}</span>
        @endif
    </x-ui.btn>
    @endforeach
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
    @forelse($auctions as $auction)
    <a href="{{ route('auctions.show', $auction) }}" class="auc-card" style="text-decoration:none">
        <div class="auc-img">
            @php $cover = $auction->coverPhotoUrl(); @endphp
            @if($cover)
            <img src="{{ $cover }}" alt="{{ $auction->title_ar }}" loading="lazy">
            @else
            <svg width="54" height="54" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.4"><path d="m14.5 17.5 3 3 3-3"/><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/></svg>
            @endif
            <span class="auc-tag {{ $auction->isLive() ? 'live' : '' }}">
                @if($auction->isLive())<span class="dot"></span>@endif
                {{ $auction->status->label() }}
            </span>
        </div>
        <div class="auc-body">
            <div class="auc-cat">{{ $auction->category?->name }}</div>
            <div class="auc-ttl">{{ $auction->title_ar }}</div>
            <div class="auc-loc"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ $auction->wilaya?->name }}</div>
        </div>
        <div class="auc-foot">
            <div class="pr"><div class="lbl">{{ __('auctions.current_price') }}</div><div class="pv num">{{ dzd($auction->currentPrice()) }}</div></div>
            <div class="bids"><div class="n num">{{ $auction->bidCount() }}</div> {{ __('auctions.bids_word') }}</div>
        </div>
    </a>
    @empty
    <div class="col-span-full text-center text-muted py-12">
        <svg width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.2;margin-bottom:12px"><path d="m14.5 17.5 3 3 3-3"/><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/></svg>
        <p>{{ __('dashboard.no_auctions_section') }}</p>
    </div>
    @endforelse
</div>

@endsection
