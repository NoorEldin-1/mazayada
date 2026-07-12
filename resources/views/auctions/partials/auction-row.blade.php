{{--
    Horizontal auction card for the browse listing (one per row, 5 per page).
    Expects: $auction (with entity/category/wilaya loaded).
--}}
@php
    $cover = $auction->coverPhotoUrl();
    $mediaCount = count($auction->photoUrls());
    $hasVideo = (bool) $auction->video;
@endphp
<a href="{{ route('auctions.show', $auction) }}" class="auc-row @if($auction->requires_commerce_register) cr-required @endif">
    <div class="auc-row-img">
        @if($cover)
            <img src="{{ $cover }}" alt="{{ $auction->localizedTitle() }}" loading="lazy">
        @else
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 3.5l6 6M3 21l1.5-4.5L17 4a1.41 1.41 0 0 1 2 2L6.5 18.5 3 21z"/><path d="M15 6l3 3"/></svg>
        @endif
        @if($auction->isLive())
            <span class="auc-tag live"><span class="dot"></span> {{ __('auctions.live') }}</span>
        @else
            <span class="auc-tag">{{ $auction->status->label() }}</span>
        @endif
    </div>

    <div class="auc-row-body">
        <div class="auc-row-meta">
            <span class="auc-type {{ $auction->auction_type === \App\Enums\AuctionType::SALE ? 'sale' : 'lease' }}">{{ $auction->auction_type->label() }}</span>
            @if($auction->requires_commerce_register)
                <span class="cr-badge" title="{{ __('auctions.show.spec_requires_cr') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    {{ __('auctions.show.spec_requires_cr') }}
                </span>
            @endif
            @if($auction->category)
                <span class="auc-row-cat">{{ $auction->category->name }}</span>
            @endif
        </div>
        <span class="auc-row-ttl">{{ $auction->localizedTitle() }}</span>
        <div class="auc-row-sub">
            <span class="auc-loc">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                {{ $auction->wilaya->name ?? $auction->asset_location ?? '--' }}
            </span>
            @if($mediaCount || $hasVideo)
                <span class="auc-row-media">
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
    </div>

    <div class="auc-row-foot">
        <div class="auc-row-price">
            <div class="lbl">{{ __('auctions.current_price') }}</div>
            <div class="pv"><x-money :centimes="$auction->currentPrice()" /></div>
        </div>
        <div class="auc-row-bids">
            <span class="n num">{{ $auction->bidCount() }}</span>
            <span class="w">{{ __('auctions.bids_word') }}</span>
        </div>
    </div>
</a>
