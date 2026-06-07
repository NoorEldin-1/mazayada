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
            {{-- Gallery — swipeable photo carousel when present, placeholder otherwise (spec §4 step 1) --}}
            @php $photoUrls = $auction->photoUrls(); $photoCount = count($photoUrls); @endphp
            @if($photoCount)
                <div class="ad-gallery" data-gallery>
                    <div class="ad-hero">
                        <div class="ad-hero-track" data-gtrack>
                            @foreach($photoUrls as $url)
                                <div class="ad-hero-slide"><img src="{{ $url }}" alt="{{ $auction->title_ar }}"></div>
                            @endforeach
                        </div>
                        @if($photoCount > 1)
                            <button type="button" class="ad-nav ad-nav-prev" data-gprev aria-label="{{ __('auctions.show.gallery_prev') }}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <button type="button" class="ad-nav ad-nav-next" data-gnext aria-label="{{ __('auctions.show.gallery_next') }}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                            <span class="ad-hero-count num" data-gcount>1 / {{ $photoCount }}</span>
                        @endif
                    </div>
                    @if($photoCount > 1)
                        <div class="ad-thumbs">
                            @foreach($photoUrls as $i => $url)
                                <div class="ad-thumb {{ $i === 0 ? 'on' : '' }}" data-gthumb="{{ $i }}">
                                    <img src="{{ $url }}" alt="" loading="lazy">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="ad-gallery">
                    <div class="ad-hero">
                        <svg width="90" height="90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 3.5l6 6M3 21l1.5-4.5L17 4a1.41 1.41 0 0 1 2 2L6.5 18.5 3 21z"/><path d="M15 6l3 3"/></svg>
                    </div>
                    <div class="ad-thumbs">
                        @for($i = 0; $i < 5; $i++)
                            <div class="ad-thumb {{ $i === 0 ? 'on' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </div>
                        @endfor
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

            {{-- Tab Strip --}}
            <div class="tab-strip" id="tabStrip">
                <button class="on" data-tab="details">{{ __('auctions.show.tab_details') }}</button>
                <button data-tab="specs">{{ __('auctions.show.tab_specs') }}</button>
                <button data-tab="inspection">{{ __('auctions.show.tab_inspection') }}</button>
                <button data-tab="bids">{{ __('auctions.show.tab_bids') }}</button>
            </div>

            {{-- Tab: Details --}}
            <div id="tab-details">
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
            </div>

            {{-- Tab: Specs (Facts Grid) --}}
            <div id="tab-specs" style="display:none">
                <div class="facts" style="margin-bottom:24px">
                    <div>
                        <div class="l">{{ __('auctions.show.spec_opening') }}</div>
                        <div class="v">{{ dzd($auction->opening_price) }}</div>
                    </div>
                    <div>
                        <div class="l">{{ __('auctions.show.spec_deposit') }}</div>
                        <div class="v">{{ $auction->deposit_amount ? dzd($auction->deposit_amount) : '--' }}</div>
                    </div>
                    <div>
                        <div class="l">{{ __('auctions.show.spec_entry') }}</div>
                        <div class="v">{{ $auction->entry_fee ? dzd($auction->entry_fee) : '--' }}</div>
                    </div>
                    <div>
                        <div class="l">{{ __('auctions.show.spec_book') }}</div>
                        <div class="v">{{ $auction->book_price ? dzd($auction->book_price) : '--' }}</div>
                    </div>
                    <div>
                        <div class="l">{{ __('auctions.show.spec_units') }}</div>
                        <div class="v">{{ $auction->unit_count ?? 1 }}</div>
                    </div>
                    <div>
                        <div class="l">{{ __('auctions.show.spec_wilaya') }}</div>
                        <div class="v">{{ $auction->wilaya->name ?? '--' }}</div>
                    </div>
                    <div>
                        <div class="l">{{ __('auctions.show.spec_condition') }}</div>
                        <div class="v">{{ $auction->condition ? $auction->condition->label() : '--' }}</div>
                    </div>
                    <div>
                        <div class="l">{{ __('auctions.show.spec_type') }}</div>
                        <div class="v">{{ $auction->auction_type->label() }}</div>
                    </div>
                </div>
            </div>

            {{-- Tab: Inspection window + Q&A (§4 step 4) --}}
            <div id="tab-inspection" style="display:none">
                {{-- Inspection window --}}
                <div class="card" style="margin-bottom:24px">
                    <div class="card-h">
                        <h3>{{ __('inspections.window_title') }}</h3>
                    </div>
                    <div class="card-pad">
                        @if($auction->inspection_start || $auction->inspection_end || $auction->inspection_location)
                            <div class="facts">
                                @if($auction->inspection_start)
                                    <div>
                                        <div class="l">{{ __('inspections.window_from') }}</div>
                                        <div class="v">{{ $auction->inspection_start->format('Y-m-d H:i') }}</div>
                                    </div>
                                @endif
                                @if($auction->inspection_end)
                                    <div>
                                        <div class="l">{{ __('inspections.window_to') }}</div>
                                        <div class="v">{{ $auction->inspection_end->format('Y-m-d H:i') }}</div>
                                    </div>
                                @endif
                                @if($auction->inspection_location)
                                    <div>
                                        <div class="l">{{ __('inspections.window_location') }}</div>
                                        <div class="v">{{ $auction->inspection_location }}</div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <p style="margin:0;font-size:14px;color:var(--muted)">{{ __('inspections.window_none') }}</p>
                        @endif

                        {{-- Condition book download (§4 step 2) --}}
                        @if($conditionBook)
                            <a href="{{ route('documents.download', $conditionBook) }}" class="btn btn-ghost" style="margin-top:16px;display:inline-flex;align-items:center;gap:8px;text-decoration:none">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                {{ __('auctions.show.read_condition_book') }}
                            </a>
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
            </div>

            {{-- Tab: Bid History --}}
            <div id="tab-bids" style="display:none">
                <div class="card">
                    <div class="card-h">
                        <h3>{{ __('auctions.show.tab_bids') }}</h3>
                        <span class="sub">{{ __('auctions.show.recent_prefix') }} <span class="num">{{ $bids->count() }}</span> {{ __('auctions.bids_word') }}</span>
                    </div>
                    @if($bids->count())
                        <table class="tbl">
                            <thead>
                                <tr>
                                    <th>{{ __('auctions.show.th_bidder') }}</th>
                                    <th>{{ __('auctions.show.th_amount') }}</th>
                                    <th>{{ __('auctions.show.th_time') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bids as $bid)
                                    <tr>
                                        <td style="font-weight:600">{{ $bid->bidderAlias() }}</td>
                                        <td><span class="num" style="font-weight:700;color:var(--primary)">{{ dzd($bid->amount) }}</span></td>
                                        <td style="color:var(--muted);font-size:12px">{{ $bid->bid_time->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="card-pad" style="text-align:center;color:var(--muted);padding:32px">
                            {{ __('auctions.show.no_bids') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column (Sidebar) --}}
        <div class="ad-side">
            {{-- Bid Panel --}}
            <div class="bid-panel">
                @if($auction->isLive())
                    <div class="live">
                        <span class="d"></span>
                        {{ __('auctions.live') }}
                    </div>
                @endif

                <div class="cur-l">{{ __('auctions.current_price') }}</div>
                <div class="cur-v num">{{ dzd($auction->currentPrice()) }}</div>
                <div class="cur-s"><span class="num">{{ $auction->bidCount() }}</span> {{ __('auctions.show.bids_so_far') }}</div>

                {{-- Countdown --}}
                @if($auction->isLive() && $auction->end_time)
                    <div class="countdown" data-end="{{ $auction->end_time->toIso8601String() }}">
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

                @if($auction->isLive())
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
                        @elseif(!$participant || !$participant->condition_book_acknowledged_at)
                            {{-- §10.3 — must acknowledge the condition book before registering --}}
                            @if($conditionBook)
                                <a href="{{ route('documents.download', $conditionBook) }}" style="display:inline-flex;align-items:center;gap:6px;margin-top:14px;color:var(--accent);font-size:13px;font-weight:600;text-decoration:none">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    {{ __('auctions.show.read_condition_book') }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('auctions.acknowledge-book', $auction) }}" style="margin-top:14px">
                                @csrf
                                <label style="display:flex;gap:8px;align-items:flex-start;font-size:12px;color:rgba(255,255,255,.85);margin-bottom:10px;cursor:pointer">
                                    <input type="checkbox" required style="margin-top:3px">
                                    <span>{{ __('auctions.show.ack_book') }}</span>
                                </label>
                                <button type="submit" class="bid-cta">{{ __('auctions.show.ack_submit') }}</button>
                            </form>
                        @elseif(!$participant->isFullyRegistered())
                            {{-- Acknowledged → pay deposit + entry fee to register (§4 step 3) --}}
                            <form method="POST" action="{{ route('auctions.register', $auction) }}" style="margin-top:14px">
                                @csrf
                                <button type="submit" class="bid-cta">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                                    {{ __('auctions.show.register_in') }}
                                </button>
                            </form>
                        @else
                            {{-- Registered Participant: Can Bid --}}
                            <form method="POST" action="{{ route('auctions.bid', $auction) }}" style="margin-top:14px">
                                @csrf
                                <div class="bid-quick">
                                    <button type="button" onclick="addBid(100000)">
                                        <span class="num">+1,000</span> {{ __('common.currency') }}
                                    </button>
                                    <button type="button" onclick="addBid(500000)">
                                        <span class="num">+5,000</span> {{ __('common.currency') }}
                                    </button>
                                    <button type="button" onclick="addBid(1000000)">
                                        <span class="num">+10,000</span> {{ __('common.currency') }}
                                    </button>
                                </div>
                                <div class="bid-input">
                                    <input type="number" name="amount" id="bidAmount" placeholder="{{ __('auctions.show.amount_placeholder') }}" min="{{ $auction->currentPrice() + 1 }}" required>
                                </div>
                                <button type="submit" class="bid-cta">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 3.5l6 6M3 21l1.5-4.5L17 4a1.41 1.41 0 0 1 2 2L6.5 18.5 3 21z"/></svg>
                                    {{ __('auctions.show.place_bid') }}
                                </button>
                            </form>
                        @endif
                    @endguest
                @elseif($auction->status === \App\Enums\AuctionStatus::CLOSED)
                    {{-- Auction Closed --}}
                    <div style="margin-top:18px;padding:16px;background:rgba(212,168,67,.15);border-radius:12px;border:1px solid rgba(212,168,67,.3);text-align:center">
                        <div style="font-size:12px;opacity:.8;margin-bottom:6px">{{ __('auctions.show.closed') }}</div>
                        @if($auction->winner)
                            <div style="font-size:14px;font-weight:600;color:var(--accent)">{{ __('auctions.show.winner_label') }} {{ $auction->winner->fullNameAr() }}</div>
                            <div class="num" style="font-size:20px;font-weight:700;color:var(--accent);margin-top:4px">{{ dzd($auction->final_price ?? $auction->currentPrice()) }}</div>
                            {{-- §4 step 7 — the winner pays the final amount within the legal deadline --}}
                            @auth
                                @if(auth()->id() === $auction->winner_user_id)
                                    <form method="POST" action="{{ route('auctions.final-payment', $auction) }}" style="margin-top:12px">
                                        @csrf
                                        <button type="submit" class="bid-cta">{{ __('auctions.show.pay_final') }}</button>
                                    </form>
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

            {{-- Bid History (Sidebar) --}}
            <div class="card">
                <div class="card-h">
                    <h3>{{ __('auctions.show.recent_bids') }}</h3>
                </div>
                @if($bids->count())
                    <div style="padding:8px 0">
                        @foreach($bids->take(10) as $bid)
                            <div style="display:flex;align-items:center;gap:10px;padding:10px 20px;{{ !$loop->last ? 'border-bottom:1px solid var(--line)' : '' }}">
                                <div style="width:32px;height:32px;border-radius:9px;background:var(--bg-2);display:grid;place-items:center;font-size:11px;font-weight:700;color:var(--primary);flex-shrink:0" class="num">
                                    {{ substr($bid->user_id, 0, 2) }}
                                </div>
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:13px;font-weight:600">{{ $bid->bidderAlias() }}</div>
                                    <div style="font-size:11px;color:var(--muted)">{{ $bid->bid_time->diffForHumans() }}</div>
                                </div>
                                <div class="num" style="font-weight:700;font-size:14px;color:var(--primary)">{{ dzd($bid->amount) }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="card-pad" style="text-align:center;color:var(--muted);font-size:13px">{{ __('auctions.show.no_bids_side') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Tab switching
document.querySelectorAll('#tabStrip button').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('#tabStrip button').forEach(b => b.classList.remove('on'));
        this.classList.add('on');
        document.querySelectorAll('[id^="tab-"]').forEach(t => t.style.display = 'none');
        document.getElementById('tab-' + this.dataset.tab).style.display = '';
    });
});

// Quick bid buttons
function addBid(amount) {
    document.getElementById('bidAmount').value = {{ $auction->currentPrice() }} + amount;
}

// Photo gallery — swipeable carousel (hero track + thumbnail sync), RTL-aware
(function () {
    const root = document.querySelector('[data-gallery]');
    if (!root) return;
    const track = root.querySelector('[data-gtrack]');
    if (!track) return;
    const slides = track.children;
    const count = slides.length;
    if (count <= 1) return;

    const thumbs = root.querySelectorAll('[data-gthumb]');
    const counter = root.querySelector('[data-gcount]');
    const prevBtn = root.querySelector('[data-gprev]');
    const nextBtn = root.querySelector('[data-gnext]');
    let index = 0;

    function isRtl() { return getComputedStyle(track).direction === 'rtl'; }

    function render() {
        const pct = index * 100;
        // RTL lays slides out right-to-left, so the shift sign flips.
        track.style.transform = 'translateX(' + (isRtl() ? pct : -pct) + '%)';
        for (let i = 0; i < thumbs.length; i++) {
            thumbs[i].classList.toggle('on', i === index);
        }
        if (counter) counter.textContent = (index + 1) + ' / ' + count;
    }

    function go(i) { index = (i % count + count) % count; render(); }

    if (nextBtn) nextBtn.addEventListener('click', () => go(index + 1));
    if (prevBtn) prevBtn.addEventListener('click', () => go(index - 1));
    for (let t = 0; t < thumbs.length; t++) {
        thumbs[t].addEventListener('click', function () {
            go(parseInt(this.getAttribute('data-gthumb'), 10) || 0);
        });
    }

    // Touch / pointer swipe on the hero.
    let startX = null;
    track.addEventListener('pointerdown', (e) => { startX = e.clientX; });
    track.addEventListener('pointerup', (e) => {
        if (startX === null) return;
        const dx = e.clientX - startX;
        startX = null;
        if (Math.abs(dx) > 40) {
            let forward = dx < 0;       // swipe toward inline-start = next (LTR)
            if (isRtl()) forward = !forward;
            go(forward ? index + 1 : index - 1);
        }
    });
    track.addEventListener('pointercancel', () => { startX = null; });

    render();
})();

// Countdown timer
(function() {
    const el = document.querySelector('.countdown');
    if (!el) return;
    const end = new Date(el.dataset.end).getTime();
    function tick() {
        const now = Date.now();
        let diff = Math.max(0, Math.floor((end - now) / 1000));
        const h = Math.floor(diff / 3600); diff %= 3600;
        const m = Math.floor(diff / 60);
        const s = diff % 60;
        document.getElementById('cd-h').textContent = String(h).padStart(2, '0');
        document.getElementById('cd-m').textContent = String(m).padStart(2, '0');
        document.getElementById('cd-s').textContent = String(s).padStart(2, '0');
        if (end - now > 0) requestAnimationFrame(tick);
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
@endpush
