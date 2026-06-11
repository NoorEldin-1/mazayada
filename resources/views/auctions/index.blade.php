@extends('layouts.app')

@section('title', __('nav.browse_auctions'))

@section('content')
{{-- Page Heading --}}
<div class="page-hd">
    <div class="container">
        <div class="crumbs">
            <a href="/">{{ __('nav.home') }}</a>
            <span class="sep">/</span>
            {{ __('nav.browse_auctions') }}
        </div>
        <div class="row">
            <div>
                <h1>{{ __('nav.browse_auctions') }}</h1>
                <div class="meta">{{ __('auctions.browse.total_prefix') }} <span class="num">{{ $auctions->total() }}</span> {{ __('auctions.browse.total_suffix') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding-top:28px;padding-bottom:48px">

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('auctions.index') }}" class="card" style="margin-bottom:24px">
        <div class="card-pad" style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end">
            <div class="field" style="flex:1;min-width:200px">
                <label>{{ __('common.search') }}</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="q" value="{{ request('q') }}" class="input has-ic" placeholder="{{ __('home.search_placeholder') }}">
                </div>
            </div>
            <div class="field" style="min-width:160px">
                <label>{{ __('auctions.browse.filter_category') }}</label>
                <select name="category" class="select">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($categories ?? [] as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="min-width:160px">
                <label>{{ __('auctions.browse.filter_wilaya') }}</label>
                <select name="wilaya" class="select">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($wilayas ?? [] as $w)
                        <option value="{{ $w->id }}" {{ request('wilaya') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="min-width:140px">
                <label>{{ __('auctions.browse.filter_status') }}</label>
                <select name="status" class="select">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach(\App\Enums\AuctionStatus::cases() as $s)
                        <option value="{{ $s->value }}" {{ request('status') == $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="min-width:130px">
                <label>{{ __('auctions.browse.filter_type') }}</label>
                <select name="type" class="select">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach(\App\Enums\AuctionType::cases() as $t)
                        <option value="{{ $t->value }}" {{ request('type') == $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                {{ __('common.search') }}
            </button>
        </div>
    </form>

    {{-- Auction Grid --}}
    @if($auctions->count())
        <div class="auc-grid">
            @foreach($auctions as $auction)
                @php
                    $cover = $auction->coverPhotoUrl();
                    $mediaCount = count($auction->photoUrls());
                    $hasVideo = (bool) $auction->video;
                @endphp
                <a href="{{ route('auctions.show', $auction) }}" class="auc-card">
                    <div class="auc-img">
                        @if($cover)
                            <img src="{{ $cover }}" alt="{{ $auction->title_ar }}" loading="lazy">
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 3.5l6 6M3 21l1.5-4.5L17 4a1.41 1.41 0 0 1 2 2L6.5 18.5 3 21z"/><path d="M15 6l3 3"/></svg>
                        @endif
                        @if($auction->isLive())
                            <span class="auc-tag live"><span class="dot"></span> {{ __('auctions.live') }}</span>
                        @else
                            <span class="auc-tag">{{ $auction->status->label() }}</span>
                        @endif
                        @if($mediaCount || $hasVideo)
                            <span class="auc-media">
                                @if($mediaCount)
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                    <span class="num">{{ $mediaCount }}</span>
                                @endif
                                @if($hasVideo)
                                    @if($mediaCount)<span class="sep">·</span>@endif
                                    <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                @endif
                            </span>
                        @endif
                    </div>
                    <div class="auc-body">
                        <span class="auc-type {{ $auction->auction_type === \App\Enums\AuctionType::SALE ? 'sale' : 'lease' }}">{{ $auction->auction_type->label() }}</span>
                        <span class="auc-cat">{{ $auction->category->name ?? '' }}</span>
                        <span class="auc-ttl">{{ $auction->title_ar }}</span>
                        <span class="auc-loc">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ $auction->wilaya->name ?? $auction->asset_location ?? '--' }}
                        </span>
                    </div>
                    <div class="auc-foot">
                        <div class="pr">
                            <div class="lbl">{{ __('auctions.current_price') }}</div>
                            <div class="pv"><x-money :centimes="$auction->currentPrice()" /></div>
                        </div>
                        <div class="bids">
                            <div class="n num">{{ $auction->bidCount() }}</div>
                            <span class="w">{{ __('auctions.bids_word') }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div style="margin-top:28px;display:flex;justify-content:center">
            {{ $auctions->withQueryString()->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="card" style="text-align:center;padding:64px 24px">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--muted-2)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 18px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <h3 style="margin:0 0 8px;font-size:18px;font-weight:600;color:var(--ink-2)">{{ __('auctions.browse.none_title') }}</h3>
            <p style="margin:0;color:var(--muted);font-size:14px">{{ __('auctions.browse.none_desc') }}</p>
        </div>
    @endif
</div>
@endsection
