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

@php
    // Active-filter chips: each removable link is the current query minus one
    // filter (and never carrying `page`, so removing a filter resets to page 1).
    $qs = request()->query();
    unset($qs['page']);

    $removeUrl = function (array $without) use ($qs) {
        $copy = $qs;
        foreach ($without as $key => $value) {
            if ($value === null) {
                unset($copy[$key]);
            } else {
                $copy[$key] = array_values(array_diff((array) ($copy[$key] ?? []), [$value]));
                if (empty($copy[$key])) {
                    unset($copy[$key]);
                }
            }
        }
        return route('auctions.index', $copy);
    };

    $statusLabels = [
        'upcoming' => __('auctions.browse.status_upcoming'),
        'live' => __('auctions.browse.status_live'),
        'closed' => __('auctions.browse.status_closed'),
    ];

    $chips = [];

    if (request()->filled('q')) {
        $chips[] = ['label' => '«'.request('q').'»', 'url' => $removeUrl(['q' => null])];
    }
    if (request()->filled('type') && ($typeCase = \App\Enums\AuctionType::tryFrom(request('type')))) {
        $chips[] = ['label' => $typeCase->label(), 'url' => $removeUrl(['type' => null])];
    }
    foreach ((array) request('status', []) as $tok) {
        if (isset($statusLabels[$tok])) {
            $chips[] = ['label' => $statusLabels[$tok], 'url' => $removeUrl(['status' => $tok])];
        }
    }
    if (request()->filled('category') && ($cat = ($categories ?? collect())->firstWhere('id', request('category')))) {
        $chips[] = ['label' => $cat->name, 'url' => $removeUrl(['category' => null])];
    }
    if (request()->filled('wilaya') && ($wil = ($wilayas ?? collect())->firstWhere('id', request('wilaya')))) {
        // Removing the wilaya also clears the (now-orphaned) commune.
        $chips[] = ['label' => $wil->name, 'url' => $removeUrl(['wilaya' => null, 'commune' => null])];
    }
    if (request()->filled('commune') && ($com = ($communes ?? collect())->firstWhere('id', request('commune')))) {
        $chips[] = ['label' => $com->name, 'url' => $removeUrl(['commune' => null])];
    }
    foreach ((array) request('asset_class', []) as $ac) {
        if ($case = \App\Enums\AssetClass::tryFrom($ac)) {
            $chips[] = ['label' => $case->label(), 'url' => $removeUrl(['asset_class' => $ac])];
        }
    }
    foreach ((array) request('condition', []) as $cond) {
        if ($case = \App\Enums\AssetCondition::tryFrom($cond)) {
            $chips[] = ['label' => $case->label(), 'url' => $removeUrl(['condition' => $cond])];
        }
    }
    if (request()->filled('price_min')) {
        $chips[] = ['label' => __('auctions.browse.price_min').': '.number_format((int) request('price_min'), 0, ',', ' '), 'url' => $removeUrl(['price_min' => null])];
    }
    if (request()->filled('price_max')) {
        $chips[] = ['label' => __('auctions.browse.price_max').': '.number_format((int) request('price_max'), 0, ',', ' '), 'url' => $removeUrl(['price_max' => null])];
    }
    if (request('requires_cr') === '1' || request('requires_cr') === '0') {
        $crLabel = request('requires_cr') === '1' ? __('auctions.browse.cr_yes') : __('auctions.browse.cr_no');
        $chips[] = ['label' => __('auctions.browse.filter_requires_cr').': '.$crLabel, 'url' => $removeUrl(['requires_cr' => null])];
    }
@endphp

<div class="container br-container">
    <div class="br-grid">

        {{-- Sidebar: live search + advanced filters --}}
        @include('auctions.partials.filters-sidebar')

        {{-- Main column --}}
        <div class="br-main">

            {{-- Mobile: filters toggle + result count --}}
            <div class="br-bar">
                <button type="button" class="btn btn-outline br-mobile-toggle" data-filters-toggle aria-controls="brSide" aria-expanded="false">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    {{ __('auctions.browse.filters_toggle') }}
                    @if(count($chips))<span class="br-mobile-count">{{ count($chips) }}</span>@endif
                </button>
            </div>

            {{-- Active filter chips --}}
            @if(count($chips))
                <div class="active-filters">
                    <span class="af-label">{{ __('auctions.browse.active_filters') }}</span>
                    @foreach($chips as $chip)
                        <a href="{{ $chip['url'] }}" class="af-chip" aria-label="{{ __('auctions.browse.remove_filter') }}">
                            {{ $chip['label'] }}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </a>
                    @endforeach
                    <a href="{{ route('auctions.index') }}" class="af-clear">{{ __('auctions.browse.reset') }}</a>
                </div>
            @endif

            {{-- Auction list --}}
            @if($auctions->count())
                <div class="auc-list">
                    @foreach($auctions as $auction)
                        @include('auctions.partials.auction-row', ['auction' => $auction])
                    @endforeach
                </div>

                {{-- Pagination (carries active filters via withQueryString) --}}
                <div class="br-pgn">
                    {{ $auctions->links() }}
                </div>
            @else
                {{-- Empty State --}}
                <div class="card br-empty">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--muted-2)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <h3>{{ __('auctions.browse.none_title') }}</h3>
                    <p>{{ __('auctions.browse.none_desc') }}</p>
                    @if(count($chips))
                        <a href="{{ route('auctions.index') }}" class="btn btn-outline">{{ __('auctions.browse.reset') }}</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
    <script src="/js/auctions-browse.js?v={{ filemtime(public_path('js/auctions-browse.js')) }}"></script>
@endpush
@endsection
