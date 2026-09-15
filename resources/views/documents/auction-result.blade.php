@extends('documents._layout')

@section('doc-content')
    {{-- وصل نتيجة المزايدة (edit 23). Facts come from the signed $result meta. --}}
    <div class="section">
        <h3>{{ __('documents.auction_result.auction_section') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('documents.participation_receipt.reference') }}</td><td>{{ $result['session_code'] }}</td></tr>
            <tr><td class="k">{{ __('documents.award.asset_title') }}</td><td>{{ $auction->localizedTitle() }}</td></tr>
            <tr><td class="k">{{ __('documents.award.entity') }}</td><td>{{ $auction->entity?->name }}</td></tr>
            <tr><td class="k">{{ __('documents.participation_receipt.wilaya') }}</td><td>{{ $auction->wilaya?->name }}</td></tr>
            <tr><td class="k">{{ __('documents.participation_receipt.session_round') }}</td><td>{{ $result['session_round'] }}</td></tr>
            <tr><td class="k">{{ __('documents.participation_receipt.start_time') }}</td><td>{{ optional($auction->start_time)->format('Y-m-d H:i') }}</td></tr>
            <tr><td class="k">{{ __('documents.award.closed_at') }}</td><td>{{ $result['closed_at'] ? \Illuminate\Support\Carbon::parse($result['closed_at'])->format('Y-m-d H:i') : '' }}</td></tr>
            <tr><td class="k">{{ __('documents.participation_receipt.opening_price') }}</td><td>{!! dzd_pdf((int) $auction->opening_price) !!}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>{{ __('documents.auction_result.result_section') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('documents.auction_result.outcome') }}</td><td>{{ $result['has_winner'] ? __('auctions.session.result_awarded') : __('auctions.session.result_no_bids') }}</td></tr>
            @if($result['has_winner'])
            <tr><td class="k">{{ __('documents.auction_result.winner_alias') }}</td><td style="direction:ltr">{{ $result['winner_alias'] }}</td></tr>
            <tr><td class="k">{{ __('documents.auction_result.final_price') }}</td><td>{!! dzd_pdf((int) $result['final_price']) !!}</td></tr>
            @endif
            <tr><td class="k">{{ __('documents.auction_result.bid_count') }}</td><td>{{ $result['bid_count'] }}</td></tr>
            <tr><td class="k">{{ __('documents.auction_result.bidder_count') }}</td><td>{{ $result['bidder_count'] }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>{{ __('documents.auction_result.citizen_section') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('documents.award.winner_name') }}</td><td>{{ $user->fullNameAr() }}</td></tr>
            <tr><td class="k">{{ __('documents.award.winner_nin') }}</td><td>{{ $result['nin_masked'] }}</td></tr>
            <tr><td class="k">{{ __('documents.auction_result.my_best_bid') }}</td><td>@if($result['my_best_bid'] !== null){!! dzd_pdf((int) $result['my_best_bid']) !!}@else{{ __('documents.auction_result.no_bid') }}@endif</td></tr>
            <tr><td class="k">{{ __('documents.auction_result.my_rank') }}</td><td>{{ $result['my_rank'] !== null ? $result['my_rank'].' / '.$result['bidder_count'] : '—' }}</td></tr>
            <tr><td class="k">{{ __('documents.auction_result.standing') }}</td><td>{{ $result['is_winner'] ? __('documents.auction_result.you_won') : __('documents.auction_result.you_did_not_win') }}</td></tr>
        </table>
    </div>

    <div class="legal">{{ __('documents.auction_result.legal_notice') }}</div>
@endsection
