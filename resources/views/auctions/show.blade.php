@extends('layouts.app')

@section('title', $auction->title_ar)

@section('content')
<div class="container" style="padding-top:28px;padding-bottom:48px">

    {{-- Back Link --}}
    <a href="{{ route('auctions.index') }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--muted);font-size:13px;font-weight:500;margin-bottom:18px">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        {{ __('auctions.show.back') }}
    </a>

    @if(session('success'))
        <div style="background:#E5F3EC;border:1px solid #2D8F6A;color:#1d6045;padding:14px 18px;border-radius:12px;margin-bottom:18px;font-size:14px;font-weight:500">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#FBE2E0;border:1px solid #D9544E;color:#8E2F2A;padding:14px 18px;border-radius:12px;margin-bottom:18px;font-size:14px;font-weight:500">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div style="background:#FBE2E0;border:1px solid #D9544E;color:#8E2F2A;padding:14px 18px;border-radius:12px;margin-bottom:18px;font-size:14px;font-weight:500">
            <ul style="margin:0;padding:0 18px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="ad-grid">
        {{-- Left Column (Main) --}}
        <div>
            {{-- Gallery — swipeable media carousel (photos + one short video), placeholder otherwise (spec §4 step 1) --}}
            @php
                $photoUrls = $auction->photoUrls();
                $videoUrl = $auction->videoUrl();
                // Unified media list: photos first, the single short video last.
                $media = array_map(fn ($u) => ['type' => 'image', 'url' => $u], $photoUrls);
                if ($videoUrl) { $media[] = ['type' => 'video', 'url' => $videoUrl]; }
                $mediaCount = count($media);
                // Participation-deposit percentage, trimmed for display (10.00 → "10").
                $depositPct = rtrim(rtrim(number_format((float) $auction->deposit_percent, 2, '.', ''), '0'), '.');
            @endphp
            @if($mediaCount)
                <div class="ad-gallery"
                     data-gallery
                     dir="{{ locale_dir() }}"
                     data-a11y-prev="{{ __('auctions.show.gallery_prev') }}"
                     data-a11y-next="{{ __('auctions.show.gallery_next') }}"
                     data-a11y-close="{{ __('auctions.show.gallery_close') }}"
                     data-a11y-zoom="{{ __('auctions.show.gallery_zoom_hint') }}">
                    <div class="ad-hero">
                        <div class="swiper ad-hero-swiper" data-hero>
                            <div class="swiper-wrapper">
                                @foreach($media as $m)
                                    <div class="swiper-slide ad-hero-slide" data-type="{{ $m['type'] }}">
                                        @if($m['type'] === 'video')
                                            <video controls preload="metadata" playsinline src="{{ $m['url'] }}"></video>
                                        @else
                                            <img src="{{ $m['url'] }}" alt="{{ $auction->title_ar }}" loading="lazy">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if($mediaCount > 1)
                            <button type="button" class="ad-nav ad-nav-prev" data-prev aria-label="{{ __('auctions.show.gallery_prev') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <button type="button" class="ad-nav ad-nav-next" data-next aria-label="{{ __('auctions.show.gallery_next') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                            <span class="ad-hero-count num" data-gcount>1 / {{ $mediaCount }}</span>
                        @endif

                        <button type="button" class="ad-expand" data-expand aria-label="{{ __('auctions.show.gallery_fullscreen') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
                        </button>
                    </div>

                    @if($mediaCount > 1)
                        <div class="swiper ad-thumbs" data-thumbs>
                            <div class="swiper-wrapper">
                                @foreach($media as $i => $m)
                                    <div class="swiper-slide ad-thumb {{ $m['type'] === 'video' ? 'is-video' : '' }}">
                                        @if($m['type'] === 'video')
                                            <video src="{{ $m['url'] }}#t=0.1" muted preload="metadata"></video>
                                            <span class="play" aria-label="{{ __('auctions.show.media_video') }}"><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg></span>
                                        @else
                                            <img src="{{ $m['url'] }}" alt="" loading="lazy">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="ad-gallery ad-gallery--empty">
                    <div class="ad-hero">
                        <svg width="90" height="90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 3.5l6 6M3 21l1.5-4.5L17 4a1.41 1.41 0 0 1 2 2L6.5 18.5 3 21z"/><path d="M15 6l3 3"/></svg>
                    </div>
                </div>
            @endif

            {{-- Title + Status --}}
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px">
                <h1 style="margin:0;font-size:26px;font-weight:700;letter-spacing:-.4px">{{ $auction->title_ar }}</h1>
                <span class="chip {{ $auction->status->chipClass() }}">
                    <span class="dot"></span>
                    {{ $auction->status->label() }}
                </span>
            </div>

            {{-- Meta Info --}}
            <div style="display:flex;flex-wrap:wrap;gap:18px;margin-bottom:24px;font-size:13px;color:var(--muted)">
                <span style="display:flex;align-items:center;gap:5px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    {{ $auction->wilaya->name ?? $auction->asset_location ?? '--' }}
                </span>
                <span>{{ $auction->category->name ?? '--' }}</span>
                <span>{{ $auction->entity->name ?? '--' }}</span>
                <span class="chip {{ $auction->auction_type === \App\Enums\AuctionType::SALE ? 'chip-info' : 'chip-violet' }}">
                    <span class="dot"></span>
                    {{ $auction->auction_type->label() }}
                </span>
                @if($auction->condition)
                    <span>{{ __('common.status') }}: {{ $auction->condition->label() }}</span>
                @endif
            </div>

            {{-- Tab bar (segmented control) — shows one section at a time so the page
                 stays compact. "Details + specifications" are merged into one tab.
                 Toggle + deep-link logic live in @push('scripts'); the live bid IDs
                 inside #sec-bids are preserved untouched. --}}
            <nav class="detail-nav" id="detailNav" aria-label="{{ __('auctions.show.tab_overview') }}">
                <a href="#sec-overview" class="is-active" data-navlink="sec-overview">{{ __('auctions.show.tab_overview') }}</a>
                <a href="#sec-inspection" data-navlink="sec-inspection">{{ __('auctions.show.tab_inspection') }}</a>
                <a href="#sec-bids" data-navlink="sec-bids">{{ __('auctions.show.tab_bids') }}</a>
                {{-- § الطعون — only for an eligible bidder or someone who already filed. --}}
                @if($canAppeal || $userAppeal)
                    <a href="#sec-appeals" data-navlink="sec-appeals">{{ __('appeals.tab') }}</a>
                @endif
            </nav>

            {{-- Section: Overview — merged "details + specifications" tab (default,
                 visible). A full-width description sits above the wide specs card,
                 which is paired with a narrow stacked column (entity + location).
                 The condition-book download now lives prominently in the sidebar,
                 so it is no longer repeated here. --}}
            <section id="sec-overview" class="detail-sec">
                {{-- Description --}}
                <div class="card" style="margin-bottom:24px">
                    <div class="card-h">
                        <h3>{{ __('auctions.show.desc_title') }}</h3>
                    </div>
                    <div class="card-pad">
                        <p style="margin:0;font-size:14px;line-height:1.8;color:var(--ink-2)">
                            {{ $auction->description_ar ?? __('auctions.show.no_desc') }}
                        </p>
                    </div>
                </div>

                {{-- Specifications (wide) | managing entity + location (narrow) --}}
                <div class="detail-cols">
                    {{-- Specifications — clean label/value rows, grouped (wide column) --}}
                    <div class="card">
                        <div class="card-h">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg>
                            <h3>{{ __('auctions.show.tab_specs') }}</h3>
                        </div>
                        <div class="card-pad">
                            {{-- Group: admin-authored asset specifications (dynamic,
                                 localized title+body blocks). Stacked layout — bodies
                                 may be prose; pre-line keeps the admin's line breaks
                                 while {{ }} auto-escaping stays XSS-safe. --}}
                            @php $assetSpecs = $auction->localizedSpecifications(); @endphp
                            @if(count($assetSpecs))
                                <div class="spec-group">
                                    <h4 class="spec-h">{{ __('auctions.show.specs_group_asset_specs') }}</h4>
                                    @foreach($assetSpecs as $spec)
                                        <div style="padding:9px 0;{{ $loop->last ? '' : 'border-bottom:1px solid var(--line)' }}">
                                            <div style="font-size:13.5px;font-weight:700;color:var(--ink);margin-bottom:4px">{{ $spec['title'] }}</div>
                                            <p style="margin:0;font-size:13.5px;line-height:1.8;color:var(--ink-2);white-space:pre-line">{{ $spec['body'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Group: pricing — what a participant pays (book + refundable deposit) --}}
                            <div class="spec-group">
                                <h4 class="spec-h">{{ __('auctions.show.specs_group_pricing') }}</h4>
                                <div class="spec-rows">
                                    <div class="spec-row"><span class="l">{{ __('auctions.show.spec_opening') }}</span><span class="v"><x-money :centimes="$auction->opening_price" /></span></div>
                                    <div class="spec-row"><span class="l">{{ __('auctions.show.spec_deposit') }}</span><span class="v">@if($auction->deposit_amount)<x-money :centimes="$auction->deposit_amount" /> <span class="num" style="color:var(--muted);font-size:12px">({{ $depositPct }}%)</span>@else--@endif</span></div>
                                    <div class="spec-row"><span class="l">{{ __('auctions.show.spec_book') }}</span><span class="v">@if($auction->book_price)<x-money :centimes="$auction->book_price" />@else{{ __('auctions.show.book_free') }}@endif</span></div>
                                </div>
                                <p style="margin:10px 0 0;font-size:12px;color:var(--muted);line-height:1.7">{{ __('auctions.show.deposit_refundable_hint') }}</p>
                            </div>

                            {{-- Group: the asset --}}
                            <div class="spec-group">
                                <h4 class="spec-h">{{ __('auctions.show.specs_group_asset') }}</h4>
                                <div class="spec-rows">
                                    <div class="spec-row"><span class="l">{{ __('auctions.show.spec_category') }}</span><span class="v">{{ $auction->category->name ?? '--' }}</span></div>
                                    <div class="spec-row"><span class="l">{{ __('auctions.show.spec_type') }}</span><span class="v">{{ $auction->auction_type->label() }}</span></div>
                                    <div class="spec-row"><span class="l">{{ __('auctions.show.spec_asset_class') }}</span><span class="v">{{ $auction->asset_class?->label() ?? '--' }}</span></div>
                                    <div class="spec-row"><span class="l">{{ __('auctions.show.spec_condition') }}</span><span class="v">{{ $auction->condition ? $auction->condition->label() : '--' }}</span></div>
                                    <div class="spec-row"><span class="l">{{ __('auctions.show.spec_units') }}</span><span class="v num">{{ $auction->unit_count ?? 1 }}</span></div>
                                    <div class="spec-row"><span class="l">{{ __('auctions.show.spec_requires_cr') }}</span><span class="v">{{ $auction->requires_commerce_register ? __('common.yes') : __('common.no') }}</span></div>
                                </div>
                            </div>

                            {{-- Group: lease terms (LEASE only) --}}
                            @if($auction->auction_type === \App\Enums\AuctionType::LEASE)
                                <div class="spec-group">
                                    <h4 class="spec-h">{{ __('auctions.show.specs_group_lease') }}</h4>
                                    <div class="spec-rows">
                                        <div class="spec-row"><span class="l">{{ __('auctions.show.spec_lease_duration') }}</span><span class="v num">{{ $auction->lease_duration_years ?? '--' }}</span></div>
                                        <div class="spec-row"><span class="l">{{ __('auctions.show.spec_lease_renewals') }}</span><span class="v num">{{ $auction->lease_renewals ?? '--' }}</span></div>
                                    </div>
                                </div>
                            @endif

                            {{-- Group: schedule --}}
                            <div class="spec-group">
                                <h4 class="spec-h">{{ __('auctions.show.specs_group_schedule') }}</h4>
                                <div class="spec-rows">
                                    <div class="spec-row"><span class="l">{{ __('auctions.show.spec_start') }}</span><span class="v num">{{ $auction->start_time?->format('Y-m-d H:i') ?? '--' }}</span></div>
                                    <div class="spec-row"><span class="l">{{ __('auctions.show.spec_end') }}</span><span class="v num">{{ $auction->end_time?->format('Y-m-d H:i') ?? '--' }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Narrow column: entity + location + documents, stacked to fill
                         the height beside the tall specs card (avoids a big gap). --}}
                    <div class="detail-aside">
                        {{-- Managing entity — display only (no public entity profile exists) --}}
                        <aside class="card entity-card">
                            <div class="card-h">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><line x1="9" y1="9" x2="9" y2="9.01"/><line x1="9" y1="13" x2="9" y2="13.01"/></svg>
                                <h3>{{ __('auctions.show.entity_card_title') }}</h3>
                            </div>
                            <div class="card-pad entity-body">
                                @if($auction->entity)
                                    <div class="entity-badge num">{{ $auction->entity->type?->code() ?? '—' }}</div>
                                    <div class="entity-name">{{ $auction->entity->name }}</div>
                                    @if($auction->entity->type)
                                        <div class="entity-sub">{{ $auction->entity->type->label() }}</div>
                                    @endif
                                    @if($auction->entity->phone || $auction->entity->email)
                                        <div class="entity-contact">
                                            @if($auction->entity->phone)
                                                <div class="ec-row" title="{{ __('auctions.show.entity_phone') }}">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                                    <span class="num" dir="ltr">{{ $auction->entity->phone }}</span>
                                                </div>
                                            @endif
                                            @if($auction->entity->email)
                                                <div class="ec-row" title="{{ __('auctions.show.entity_email') }}">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                                    <span dir="ltr">{{ $auction->entity->email }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <div class="entity-sub">--</div>
                                @endif
                            </div>
                        </aside>

                        {{-- Asset location — text + Google Maps directions (when geo-coded) --}}
                        <aside class="card loc-card">
                            <div class="card-h">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <h3>{{ __('auctions.show.location_card_title') }}</h3>
                            </div>
                            <div class="card-pad">
                                <div class="spec-rows">
                                    @if($auction->asset_location)
                                        <div class="spec-row"><span class="l">{{ __('auctions.show.spec_location') }}</span><span class="v">{{ $auction->asset_location }}</span></div>
                                    @endif
                                    <div class="spec-row"><span class="l">{{ __('auctions.show.spec_wilaya') }}</span><span class="v">{{ $auction->wilaya->name ?? '--' }}</span></div>
                                    @if($auction->commune)
                                        <div class="spec-row"><span class="l">{{ __('auctions.show.spec_commune') }}</span><span class="v">{{ $auction->commune->name }}</span></div>
                                    @endif
                                    @if($auction->mayor_name)
                                        <div class="spec-row"><span class="l">{{ __('auctions.show.spec_mayor') }}</span><span class="v">{{ $auction->mayor_name }}</span></div>
                                    @endif
                                </div>
                                @if($auction->latitude && $auction->longitude)
                                    <a href="https://www.google.com/maps?q={{ $auction->latitude }},{{ $auction->longitude }}" target="_blank" rel="noopener noreferrer" class="loc-directions">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                                        {{ __('auctions.show.directions') }}
                                    </a>
                                @endif
                            </div>
                        </aside>
                    </div>
                </div>
            </section>

            {{-- Section: Inspection window + Q&A (§4 step 4) — hidden until tab selected --}}
            <section id="sec-inspection" class="detail-sec" style="display:none">
                {{-- Inspection window --}}
                <div class="card" style="margin-bottom:24px">
                    <div class="card-h">
                        <h3>{{ __('inspections.window_title') }}</h3>
                    </div>
                    <div class="card-pad">
                        @if($auction->inspection_start || $auction->inspection_end || $auction->inspection_location)
                            <div class="spec-rows">
                                @if($auction->inspection_start)
                                    <div class="spec-row"><span class="l">{{ __('inspections.window_from') }}</span><span class="v num">{{ $auction->inspection_start->format('Y-m-d H:i') }}</span></div>
                                @endif
                                @if($auction->inspection_end)
                                    <div class="spec-row"><span class="l">{{ __('inspections.window_to') }}</span><span class="v num">{{ $auction->inspection_end->format('Y-m-d H:i') }}</span></div>
                                @endif
                                @if($auction->inspection_location)
                                    <div class="spec-row"><span class="l">{{ __('inspections.window_location') }}</span><span class="v">{{ $auction->inspection_location }}</span></div>
                                @endif
                            </div>
                        @else
                            <p style="margin:0;font-size:14px;color:var(--muted)">{{ __('inspections.window_none') }}</p>
                        @endif
                    </div>
                </div>

                {{-- Q&A --}}
                <div class="card" style="margin-bottom:24px">
                    <div class="card-h">
                        <h3>{{ __('inspections.section_title') }}</h3>
                    </div>

                    {{-- Answered, public questions --}}
                    @if($questions->count())
                        <div style="padding:4px 0">
                            @foreach($questions as $q)
                                <div style="padding:14px 20px;{{ !$loop->last ? 'border-bottom:1px solid var(--line)' : '' }}">
                                    <div style="font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px">
                                        <span style="color:var(--primary);font-weight:700">{{ __('inspections.q_label') }}:</span> {{ $q->question }}
                                    </div>
                                    <div style="font-size:13px;color:var(--ink-2);line-height:1.7">
                                        <span style="color:var(--muted);font-weight:700">{{ __('inspections.a_label') }}:</span> {{ $q->answer }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="card-pad" style="text-align:center;color:var(--muted);font-size:13px">{{ __('inspections.qa_none') }}</div>
                    @endif

                    {{-- Ask form — authenticated, KYC-complete, bid-eligible users (§4 step 4) --}}
                    <div class="card-pad" style="border-top:1px solid var(--line)">
                        @guest
                            <a href="{{ route('login') }}" style="font-size:13px;color:var(--primary);font-weight:600;text-decoration:none">{{ __('inspections.ask_login') }}</a>
                        @else
                            @if(auth()->user()->isKycComplete() && auth()->user()->canBid() && !auth()->user()->isBlacklisted() && !auth()->user()->isLocked())
                                <form method="POST" action="{{ route('auctions.questions', $auction) }}">
                                    @csrf
                                    <textarea name="question" rows="3" required maxlength="1000" class="input" placeholder="{{ __('inspections.ask_placeholder') }}" style="width:100%;resize:vertical;margin-bottom:10px"></textarea>
                                    <button type="submit" class="btn btn-primary">{{ __('inspections.ask_submit') }}</button>
                                </form>
                            @else
                                <p style="margin:0;font-size:13px;color:var(--muted)">{{ __('inspections.ask_login') }}</p>
                            @endif
                        @endguest
                    </div>
                </div>
            </section>

            {{-- Section: Bid History — updated live (no reload) by resources/js/auction.js,
                 mirroring the sidebar "recent bids". The table is always rendered so
                 new rows can be prepended even from an empty start; the top row (the
                 highest = latest bid) carries the premium "highest" highlight.
                 IDs (liveBidHistory / liveBidHistoryCount / liveBidHistoryEmpty)
                 are preserved exactly — auction.js depends on them.
                 Hidden until its tab is selected; the table still updates live
                 in the background and the sidebar "recent bids" stays visible. --}}
            <section id="sec-bids" class="detail-sec" style="display:none">
                <div class="card">
                    <div class="card-h">
                        <h3>{{ __('auctions.show.tab_bids') }}</h3>
                        <span class="sub">{{ __('auctions.show.recent_prefix') }} <span class="num" id="liveBidHistoryCount">{{ $bids->count() }}</span> {{ __('auctions.bids_word') }}</span>
                    </div>
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>{{ __('auctions.show.th_bidder') }}</th>
                                <th>{{ __('auctions.show.th_amount') }}</th>
                                <th>{{ __('auctions.show.th_time') }}</th>
                            </tr>
                        </thead>
                        <tbody id="liveBidHistory">
                            @forelse($bids as $bid)
                                <tr class="bid-hist-row{{ $loop->first ? ' is-top' : '' }}">
                                    <td class="bid-hist-bidder">
                                        <span>{{ $bid->bidderAlias() }}</span>
                                        @if($loop->first)
                                            <span class="top-badge">
                                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 16 3 6l5.5 4L12 4l3.5 6L21 6l-2 10H5Zm0 3h14v2H5z"/></svg>
                                                {{ __('auctions.show.highest_badge') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="bid-hist-amt"><x-money :centimes="$bid->amount" /></td>
                                    <td class="bid-hist-time">{{ $bid->bid_time->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr id="liveBidHistoryEmpty">
                                    <td colspan="3" style="text-align:center;color:var(--muted);padding:32px">{{ __('auctions.show.no_bids') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Section: Appeals (§ الطعون) — file an appeal against the result, or
                 track an already-filed one. Rendered only for an eligible bidder
                 ($canAppeal) or someone who already filed ($userAppeal). --}}
            @if($canAppeal || $userAppeal)
            <section id="sec-appeals" class="detail-sec" style="display:none">
                @if($userAppeal)
                    {{-- Status tracking — the citizen sees only the 3 public states. --}}
                    <div class="card">
                        <div class="card-h">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 11V7a4 4 0 0 0-8 0v4"/><rect x="5" y="11" width="14" height="10" rx="2"/></svg>
                            <h3>{{ __('appeals.your_appeal_title') }}</h3>
                        </div>
                        <div class="card-pad">
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px">
                                <strong style="font-size:14px;color:var(--ink)">{{ $userAppeal->subject }}</strong>
                                <span class="chip {{ $userAppeal->status->publicChipClass() }}">{{ $userAppeal->status->publicLabel() }}</span>
                            </div>
                            <p style="margin:0 0 12px;font-size:13.5px;line-height:1.8;color:var(--ink-2);white-space:pre-line">{{ $userAppeal->reason }}</p>
                            @if($userAppeal->status->isTerminal() && $userAppeal->admin_response)
                                <div style="padding:12px 14px;background:var(--bg-2, #f6f7f5);border-radius:10px;font-size:13px;line-height:1.7;color:var(--ink-2)">
                                    <strong style="color:var(--primary)">{{ __('appeals.admin_response') }}</strong> {{ $userAppeal->admin_response }}
                                </div>
                            @endif
                            <p style="margin:12px 0 0;font-size:12px;color:var(--muted)">{{ __('appeals.submitted_on') }} {{ $userAppeal->created_at->format('Y-m-d') }}</p>
                        </div>
                    </div>
                @else
                    {{-- Submit form — eligible bidder who has not yet filed. --}}
                    <div class="card">
                        <div class="card-h">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 12h3v8h6v-5h2v5h6v-8h3z"/></svg>
                            <h3>{{ __('appeals.auction_tab_title') }}</h3>
                        </div>
                        <div class="card-pad">
                            <p style="margin:0 0 16px;font-size:13px;color:var(--muted);line-height:1.7">{{ __('appeals.window_hint', ['days' => $auction->appealWindowDays()]) }}</p>
                            <form method="POST" action="{{ route('auctions.appeals.store', $auction) }}">
                                @csrf
                                <div style="margin-bottom:14px">
                                    <label style="display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px">{{ __('appeals.subject') }} <span style="color:var(--danger)">*</span></label>
                                    <input class="input" name="subject" value="{{ old('subject') }}" maxlength="255" required placeholder="{{ __('appeals.subject_placeholder') }}" style="width:100%">
                                    @error('subject')<small style="display:block;color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</small>@enderror
                                </div>
                                <div style="margin-bottom:16px">
                                    <label style="display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px">{{ __('appeals.details') }} <span style="color:var(--danger)">*</span></label>
                                    <textarea class="input" name="reason" rows="5" maxlength="2000" required placeholder="{{ __('appeals.details_placeholder') }}" style="width:100%;resize:vertical">{{ old('reason') }}</textarea>
                                    @error('reason')<small style="display:block;color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</small>@enderror
                                </div>
                                <button type="submit" class="btn btn-primary">{{ __('appeals.submit') }}</button>
                            </form>
                        </div>
                    </div>
                @endif
            </section>
            @endif
        </div>

        {{-- Right Column (Sidebar) --}}
        <div class="ad-side">
            {{-- Bid Panel --}}
            <div class="bid-panel" id="bidPanel">
                @if($auction->isBiddable())
                    <div class="live">
                        <span class="d"></span>
                        {{ __('auctions.live') }}
                    </div>
                @endif

                <div class="cur-l">{{ __('auctions.current_price') }}</div>
                <div class="cur-v" id="liveCurrentPrice"><x-money :centimes="$auction->currentPrice()" /></div>
                <div class="cur-s"><span class="num" id="liveBidCount">{{ $auction->bidCount() }}</span> {{ __('auctions.show.bids_so_far') }}</div>

                {{-- Countdown --}}
                @if($auction->isBiddable())
                    <div class="countdown" id="bidCountdown" data-end="{{ $auction->end_time->toIso8601String() }}">
                        <div class="cd-i">
                            <div class="cd-v num" id="cd-h">00</div>
                            <div class="cd-l">{{ __('auctions.show.cd_hours') }}</div>
                        </div>
                        <div class="cd-i">
                            <div class="cd-v num" id="cd-m">00</div>
                            <div class="cd-l">{{ __('auctions.show.cd_minutes') }}</div>
                        </div>
                        <div class="cd-i">
                            <div class="cd-v num" id="cd-s">00</div>
                            <div class="cd-l">{{ __('auctions.show.cd_seconds') }}</div>
                        </div>
                    </div>
                @endif

                @if($auction->isBiddable())
                    @guest
                        {{-- Not Logged In --}}
                        <a href="{{ route('login') }}" class="bid-cta" style="margin-top:14px;text-decoration:none">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                            {{ __('auctions.show.login_to_participate') }}
                        </a>
                    @else
                        @php
                            $u = auth()->user();
                            $participant = $auction->participants()->where('user_id', $u->id)->first();
                        @endphp

                        @if($u->isBlacklisted())
                            {{-- Blacklisted — cannot participate --}}
                            <div style="margin-top:14px;padding:14px 16px;background:rgba(217,84,78,.18);border:1px solid rgba(217,84,78,.45);border-radius:12px;text-align:center;font-size:13px;color:#FCD9D6">
                                {{ __('auctions.show.cta_blocked') }}
                            </div>
                        @elseif($u->isLocked())
                            {{-- Temporarily locked --}}
                            <div style="margin-top:14px;padding:14px 16px;background:rgba(217,84,78,.18);border:1px solid rgba(217,84,78,.45);border-radius:12px;text-align:center;font-size:13px;color:#FCD9D6">
                                {{ __('auctions.show.cta_locked') }}
                            </div>
                        @elseif(! $u->isKycComplete())
                            {{-- KYC not complete — guide to verification --}}
                            <a href="{{ route('citizen.kyc') }}" class="bid-cta" style="margin-top:14px;text-decoration:none">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                {{ __('auctions.show.cta_complete_kyc') }}
                            </a>
                        @elseif(! $u->canBid())
                            {{-- Account not active for participation --}}
                            <div style="margin-top:14px;padding:14px 16px;background:rgba(212,168,67,.15);border:1px solid rgba(212,168,67,.3);border-radius:12px;text-align:center;font-size:13px;color:rgba(255,255,255,.85)">
                                {{ __('auctions.show.cta_inactive') }}
                            </div>
                        @elseif(! $hasBookAccess)
                            {{-- §4 step 2 — the condition book must be BOUGHT before registering --}}
                            <div style="margin-top:14px;font-size:12px;color:rgba(255,255,255,.8);line-height:1.7">{{ __('auctions.show.book_required_to_register') }}</div>
                            <form method="POST" action="{{ route('auctions.buy-book', $auction) }}" style="margin-top:10px">
                                @csrf
                                <button type="submit" class="bid-cta">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                                    {{ __('auctions.show.buy_condition_book') }} — <x-money :centimes="$auction->book_price" />
                                </button>
                            </form>
                        @elseif(!$participant || !$participant->isFullyRegistered())
                            {{-- Book purchased → pay the participation deposit to register (§4 step 3) --}}
                            @if($conditionBook)
                                <a href="{{ route('documents.download', $conditionBook) }}" style="display:inline-flex;align-items:center;gap:6px;margin-top:14px;color:var(--accent);font-size:13px;font-weight:600;text-decoration:none">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    {{ __('auctions.show.read_condition_book') }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('auctions.register', $auction) }}" style="margin-top:14px">
                                @csrf
                                <button type="submit" class="bid-cta">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                                    {{ __('auctions.show.register_in') }} — <x-money :centimes="$auction->deposit_amount" />
                                </button>
                            </form>
                        @else
                            {{-- Registered Participant: Can Bid — submitted over AJAX
                                 by resources/js/auction.js; falls back to a normal
                                 POST if JS is unavailable. --}}
                            {{-- The bid amount is entered in DINARS (the unit shown everywhere on
                                 this page). The min-next-bid below mirrors the live current price;
                                 the controller converts dinars→centimes on submit. --}}
                            @php $minNextDinars = intdiv($auction->currentPrice(), 100) + 1; @endphp
                            <form method="POST" action="{{ route('auctions.bid', $auction) }}" id="bidForm" style="margin-top:14px">
                                @csrf
                                <div class="bid-quick">
                                    <button type="button" data-quickbid="1000">
                                        <span class="num">+1 000</span> {{ __('common.currency') }}
                                    </button>
                                    <button type="button" data-quickbid="5000">
                                        <span class="num">+5 000</span> {{ __('common.currency') }}
                                    </button>
                                    <button type="button" data-quickbid="10000">
                                        <span class="num">+10 000</span> {{ __('common.currency') }}
                                    </button>
                                </div>
                                <div class="bid-input">
                                    <input type="text" inputmode="numeric" autocomplete="off" name="amount" id="bidAmount" placeholder="{{ __('auctions.show.amount_placeholder') }}" data-current="{{ $auction->currentPrice() }}" aria-describedby="bidMinHint" required>
                                </div>
                                <div id="bidMinHint" style="margin:8px 0 0;font-size:12px;color:rgba(255,255,255,.7)">{!! __('auctions.show.min_bid_hint', ['price' => dzd_html($minNextDinars * 100)]) !!}</div>
                                <div id="bidError" style="display:none;margin:8px 0 0;font-size:12px;font-weight:600;color:#FCD9D6;background:rgba(217,84,78,.18);border:1px solid rgba(217,84,78,.45);border-radius:10px;padding:8px 12px"></div>
                                <button type="submit" id="bidSubmit" class="bid-cta">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 3.5l6 6M3 21l1.5-4.5L17 4a1.41 1.41 0 0 1 2 2L6.5 18.5 3 21z"/></svg>
                                    {{ __('auctions.show.place_bid') }}
                                </button>
                            </form>
                        @endif
                    @endguest

                    {{-- Revealed by resources/js/auction.js the instant the
                         countdown hits zero — the panel locks client-side at once,
                         without waiting for the server close broadcast. --}}
                    <div id="bidEndedNotice" class="bid-ended" hidden>
                        <span class="spin" aria-hidden="true"></span>
                        <span id="bidEndedText">{{ __('auctions.realtime.awaiting_result') }}</span>
                    </div>
                @elseif($auction->hasEnded())
                    {{-- Clock ran out, result being finalised. Lazy close-on-view
                         (AuctionController::show) usually closes before this renders;
                         this is the no-JS / brief-gap fallback. --}}
                    <div class="bid-ended bid-ended--panel" style="margin-top:14px">
                        <span class="spin" aria-hidden="true"></span>
                        <div>
                            <div class="bid-ended-t">{{ __('auctions.show.ended_pending_title') }}</div>
                            <div class="bid-ended-s">{{ __('auctions.show.ended_pending_desc') }}</div>
                        </div>
                    </div>
                @elseif($auction->status === \App\Enums\AuctionStatus::CLOSED)
                    {{-- Auction Closed --}}
                    <div style="margin-top:18px;padding:16px;background:rgba(212,168,67,.15);border-radius:12px;border:1px solid rgba(212,168,67,.3);text-align:center">
                        <div style="font-size:12px;opacity:.8;margin-bottom:6px">{{ __('auctions.show.closed') }}</div>
                        @if($auction->winner)
                            <div style="font-size:14px;font-weight:600;color:var(--accent)">{{ __('auctions.show.winner_label') }} {{ $auction->winner->fullNameAr() }}</div>
                            <div style="font-size:20px;font-weight:700;color:var(--accent);margin-top:4px"><x-money :centimes="$auction->final_price ?? $auction->currentPrice()" /></div>
                            {{-- §4 step 7 — the winner pays the final amount within the legal deadline --}}
                            @auth
                                @if(auth()->id() === $auction->winner_user_id)
                                    @if($finalPaymentConfirmed)
                                        {{-- Final payment already settled — show confirmation, hide the CTA. --}}
                                        <div style="margin-top:12px;padding:10px 12px;background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.4);border-radius:10px;font-size:13px;font-weight:600;color:#16a34a">
                                            {{ __('auctions.show.final_paid') }}
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('auctions.final-payment', $auction) }}" style="margin-top:12px">
                                            @csrf
                                            <button type="submit" class="bid-cta">{{ __('auctions.show.pay_final') }}</button>
                                        </form>
                                    @endif
                                    {{-- §4 step 6 — the winner's signed award report (PDF + QR) --}}
                                    @if($awardDocument)
                                        <a href="{{ route('documents.download', $awardDocument) }}" style="display:inline-flex;align-items:center;gap:6px;margin-top:10px;color:var(--accent);font-size:13px;font-weight:600;text-decoration:none">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                            {{ __('auctions.show.download_award') }}
                                        </a>
                                    @endif
                                @endif
                            @endauth
                        @else
                            <div style="font-size:14px;color:rgba(255,255,255,.7)">{{ __('auctions.show.no_winner') }}</div>
                        @endif
                    </div>
                @else
                    {{-- Not Yet Active --}}
                    <div style="margin-top:18px;text-align:center;font-size:13px;opacity:.7">
                        {{ __('auctions.show.not_started') }}
                        @if($auction->start_time)
                            <div style="margin-top:6px;font-weight:600;color:var(--accent)" class="num">{{ $auction->start_time->format('Y-m-d H:i') }}</div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Participation costs + condition-book access — explained so bidders
                 know exactly what they pay: the book (paid, anyone may buy) and a
                 refundable participation deposit (spec §2 / §4 steps 2–3). --}}
            <div class="doc-card">
                <div class="doc-t">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    {{ __('auctions.show.costs_title') }}
                </div>

                {{-- Cost: condition book --}}
                <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--line)">
                    <div>
                        <div style="font-size:13px;font-weight:700;color:var(--ink)">{{ __('auctions.show.cost_book') }}</div>
                        <div style="font-size:11.5px;color:var(--ink-2);line-height:1.6;margin-top:2px">{{ __('auctions.show.cost_book_desc') }}</div>
                    </div>
                    <div style="font-size:14px;font-weight:700;color:var(--accent-2);white-space:nowrap">
                        @if($auction->book_price)<x-money :centimes="$auction->book_price" />@else{{ __('auctions.show.book_free') }}@endif
                    </div>
                </div>

                {{-- Cost: participation deposit (refundable) --}}
                <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:10px 0">
                    <div>
                        <div style="font-size:13px;font-weight:700;color:var(--ink)">{{ __('auctions.show.cost_deposit') }} <span class="num" style="color:var(--muted);font-weight:600">({{ $depositPct }}%)</span></div>
                        <div style="font-size:11.5px;color:var(--ink-2);line-height:1.6;margin-top:2px">{{ __('auctions.show.cost_deposit_desc') }}</div>
                    </div>
                    <div style="font-size:14px;font-weight:700;color:var(--accent-2);white-space:nowrap">
                        @if($auction->deposit_amount)<x-money :centimes="$auction->deposit_amount" />@else--@endif
                    </div>
                </div>

                {{-- Winner pays the remaining balance --}}
                <div style="font-size:11.5px;color:var(--ink-2);line-height:1.7;background:rgba(212,168,67,.14);border:1px solid rgba(212,168,67,.32);border-radius:10px;padding:9px 11px;margin-top:6px">
                    {{ __('auctions.show.winner_pays_rest') }}
                </div>

                {{-- Condition-book access CTA: download / buy / KYC / sign-in / pending --}}
                <div style="margin-top:14px">
                    @auth
                        @if($hasBookAccess)
                            @if($conditionBook)
                                <a href="{{ route('documents.download', $conditionBook) }}" class="doc-dl">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    {{ __('auctions.show.read_condition_book') }}
                                    <span class="pdf">PDF</span>
                                </a>
                            @else
                                <div style="font-size:12px;color:var(--ink-2);text-align:center">{{ __('auctions.show.book_pending') }}</div>
                            @endif
                        @elseif($auction->book_price)
                            @if(auth()->user()->isKycComplete() && auth()->user()->canBid() && !auth()->user()->isBlacklisted() && !auth()->user()->isLocked())
                                <form method="POST" action="{{ route('auctions.buy-book', $auction) }}">
                                    @csrf
                                    <button type="submit" class="doc-dl" style="width:100%;border:0;cursor:pointer;font:inherit">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                                        {{ __('auctions.show.buy_condition_book') }}
                                        <span class="pdf"><x-money :centimes="$auction->book_price" /></span>
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('citizen.kyc') }}" class="doc-dl">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    {{ __('auctions.show.buy_needs_kyc') }}
                                </a>
                            @endif
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="doc-dl">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                            {{ __('auctions.show.login_to_buy_book') }}
                        </a>
                    @endauth
                </div>
            </div>

            {{-- Bid History (Sidebar) --}}
            <div class="card">
                <div class="card-h">
                    <h3>{{ __('auctions.show.recent_bids') }}</h3>
                </div>
                {{-- New bids are prepended live (no reload) by resources/js/auction.js.
                     The top row (highest = latest bid) carries the premium "highest"
                     highlight; auction.js moves it as new bids arrive. --}}
                <div id="liveBidList" style="padding:8px 0">
                    @forelse($bids->take(10) as $bid)
                        <div class="bid-row{{ $loop->first ? ' is-top' : '' }}">
                            <div class="bid-row-av num">{{ mb_substr($bid->bidderAlias(), 0, 2) }}</div>
                            <div class="bid-row-main">
                                <div class="bid-row-name">
                                    <span>{{ $bid->bidderAlias() }}</span>
                                    @if($loop->first)
                                        <span class="top-badge">
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 16 3 6l5.5 4L12 4l3.5 6L21 6l-2 10H5Zm0 3h14v2H5z"/></svg>
                                            {{ __('auctions.show.highest_badge') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="bid-row-time">{{ $bid->bid_time->diffForHumans() }}</div>
                            </div>
                            <div class="bid-row-amt"><x-money :centimes="$bid->amount" /></div>
                        </div>
                    @empty
                        <div id="liveBidEmpty" class="card-pad" style="text-align:center;color:var(--muted);font-size:13px">{{ __('auctions.show.no_bids_side') }}</div>
                    @endforelse
                </div>
            </div>

            {{-- Asset location — a static Leaflet map of the asset's point, with a
                 directions link. Rendered only when the admin set coordinates. --}}
            @if($auction->latitude && $auction->longitude)
                <div class="card asset-loc-card">
                    <div class="card-h">
                        <h3>{{ __('auctions.show.location_card_title') }}</h3>
                        <a class="asset-loc-dir" target="_blank" rel="noopener"
                           href="https://www.google.com/maps/dir/?api=1&destination={{ $auction->latitude }},{{ $auction->longitude }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                            {{ __('auctions.show.get_directions') }}
                        </a>
                    </div>
                    <div class="asset-map" data-lat="{{ $auction->latitude }}" data-lng="{{ $auction->longitude }}"></div>
                    @if($auction->asset_location)
                        <div class="asset-loc-addr">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>{{ $auction->asset_location }}</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@if($auction->latitude && $auction->longitude)
    @push('styles')
        <link rel="stylesheet" href="/vendor/leaflet/leaflet.css">
    @endpush
    @push('scripts')
        <script src="/vendor/leaflet/leaflet.js"></script>
        <script src="/js/auction-map-view.js?v={{ filemtime(public_path('js/auction-map-view.js')) }}"></script>
    @endpush
@endif

@push('scripts')
<script>
// Detail tabs: show one section at a time (keeps the page compact). The bid
// history table updates live even while hidden; the sidebar mirrors it.
(function () {
    const nav = document.getElementById('detailNav');
    if (!nav) return;
    const links = Array.from(nav.querySelectorAll('a[data-navlink]'));
    const sections = links
        .map((a) => document.getElementById(a.dataset.navlink))
        .filter(Boolean);

    function activate(id, push) {
        if (!document.getElementById(id)) return;
        sections.forEach((s) => { s.style.display = s.id === id ? '' : 'none'; });
        links.forEach((a) => a.classList.toggle('is-active', a.dataset.navlink === id));
        if (push) history.replaceState(null, '', '#' + id);
    }

    links.forEach((a) => {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            activate(a.dataset.navlink, true);
        });
    });

    // Deep-link support: open the tab named in the URL hash (e.g. #sec-bids).
    const initial = (location.hash || '').replace('#', '');
    activate(links.some((a) => a.dataset.navlink === initial) ? initial : 'sec-overview', false);
})();
</script>

{{-- Live auction realtime (spec §6): Echo subscription + AJAX bidding + countdown.
     The WS config is injected server-side so the committed Vite bundle is
     environment-independent (works ws/localhost locally, wss/domain in prod). --}}
@include('partials.ws-config')
@php
    // NOTE: build the config array in a @php block, not @json([...]) — Blade's
    // directive paren-balancer mis-parses a multiline @json array with nested
    // route()/config()/__() calls and emits broken PHP (project gotcha).
    $auctionRealtimeConfig = [
        'auctionId' => $auction->id,
        'currentPrice' => $auction->currentPrice(),
        'endTime' => $auction->isLive() ? $auction->end_time?->toIso8601String() : null,
        'bidUrl' => route('auctions.bid', $auction),
        'currency' => __('common.currency'),
        'i18n' => [
            'now' => __('auctions.realtime.now'),
            'extended' => __('auctions.realtime.extended'),
            'closed' => __('auctions.realtime.closed'),
            'rate_limited' => __('auctions.bid.rate_limited', ['max' => config('mazayada.bidding.max_per_minute', 10)]),
            'too_low' => __('auctions.bid.too_low'),
            'invalid_amount' => __('auctions.bid.invalid_amount'),
            'error_generic' => __('auctions.realtime.error_generic'),
            // Template: keep a literal {price} token for the JS to substitute live.
            'min_bid' => __('auctions.show.min_bid_hint', ['price' => '{price}']),
            // Auction-ended / closed UI (client-side lock + inline result).
            'ended' => __('auctions.realtime.ended'),
            'awaiting_result' => __('auctions.realtime.awaiting_result'),
            'bid_closed_btn' => __('auctions.show.bid_closed_btn'),
            'closed_title' => __('auctions.show.closed'),
            'winner_label' => __('auctions.show.winner_label'),
            'no_winner' => __('auctions.show.no_winner'),
            // Premium "highest bid" badge text for live-built rows.
            'highest_badge' => __('auctions.show.highest_badge'),
        ],
    ];
@endphp
<script type="application/json" id="auction-realtime-config">
{!! json_encode($auctionRealtimeConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>
{{-- Both page bundles in ONE @vite call: realtime/bidding + the media gallery
     (Swiper). A single call emits one Vite client in dev (two separate calls
     would inject @vite/client twice and break module loading). --}}
@vite(['resources/js/auction.js', 'resources/js/gallery.js'])
@endpush
