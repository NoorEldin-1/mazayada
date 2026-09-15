@extends('documents._layout')

@section('doc-content')
    {{-- وصل المشاركة (edits 21 · 22). Facts come from the signed $receipt meta. --}}
    <div class="section">
        <h3>{{ __('documents.participation_receipt.auction_section') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('documents.participation_receipt.reference') }}</td><td>{{ $receipt['session_code'] }}</td></tr>
            <tr><td class="k">{{ __('documents.award.asset_title') }}</td><td>{{ $auction->localizedTitle() }}</td></tr>
            <tr><td class="k">{{ __('documents.award.entity') }}</td><td>{{ $auction->entity?->name }}</td></tr>
            <tr><td class="k">{{ __('documents.participation_receipt.wilaya') }}</td><td>{{ $auction->wilaya?->name }}</td></tr>
            <tr><td class="k">{{ __('documents.participation_receipt.session_round') }}</td><td>{{ $receipt['session_round'] }}</td></tr>
            <tr><td class="k">{{ __('documents.participation_receipt.start_time') }}</td><td>{{ optional($auction->start_time)->format('Y-m-d H:i') }}</td></tr>
            <tr><td class="k">{{ __('documents.participation_receipt.end_time') }}</td><td>{{ optional($auction->end_time)->format('Y-m-d H:i') }}</td></tr>
            <tr><td class="k">{{ __('documents.participation_receipt.opening_price') }}</td><td>{!! dzd_pdf((int) $auction->opening_price) !!}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>{{ __('documents.participation_receipt.citizen_section') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('documents.award.winner_name') }}</td><td>{{ $user?->fullNameAr() }}@if($user?->fullNameFr()) — {{ $user->fullNameFr() }}@endif</td></tr>
            <tr><td class="k">{{ __('documents.award.winner_nin') }}</td><td>{{ $receipt['nin_masked'] }}</td></tr>
            <tr><td class="k">{{ __('documents.award.winner_phone') }}</td><td>{{ $user?->phone }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>{{ __('documents.participation_receipt.participation_section') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('documents.participation_receipt.participant_no') }}</td><td>{{ $receipt['participant_no'] }}</td></tr>
            <tr><td class="k">{{ __('documents.participation_receipt.registered_at') }}</td><td>{{ $receipt['registered_at'] ? \Illuminate\Support\Carbon::parse($receipt['registered_at'])->format('Y-m-d H:i') : '' }}</td></tr>
            <tr><td class="k">{{ __('documents.participation_receipt.deposit') }}</td><td>{!! dzd_pdf((int) $receipt['deposit']) !!}</td></tr>
            <tr><td class="k">{{ __('documents.participation_receipt.book_price') }}</td><td>@if($receipt['book_price']){!! dzd_pdf((int) $receipt['book_price']) !!}@else{{ __('documents.participation_receipt.book_free') }}@endif</td></tr>
            <tr><td class="k">{{ __('documents.participation_receipt.payment_ref') }}</td><td style="direction:ltr">{{ $receipt['gateway_ref'] ?: '—' }}</td></tr>
        </table>
    </div>

    <div class="legal">{{ __('documents.participation_receipt.legal_notice') }}</div>
@endsection
