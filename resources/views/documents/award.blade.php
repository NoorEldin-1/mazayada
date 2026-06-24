@extends('documents._layout')

@section('doc-content')
    {{-- §10.2 — Award document (وثيقة الترسية). --}}
    <div class="section">
        <h3>{{ __('documents.award.winner_section') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('documents.award.winner_name') }}</td><td>{{ $winner?->fullNameAr() }} — {{ $winner?->fullNameFr() }}</td></tr>
            <tr><td class="k">{{ __('documents.award.winner_nin') }}</td><td>{{ mask_nin($winner?->nin) }}</td></tr>
            <tr><td class="k">{{ __('documents.award.winner_phone') }}</td><td>{{ $winner?->phone }}</td></tr>
            <tr><td class="k">{{ __('documents.award.winner_address') }}</td><td>{{ $winner?->address }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>{{ __('documents.award.asset_section') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('documents.award.asset_title') }}</td><td>{{ $auction->localizedTitle() }}</td></tr>
            <tr><td class="k">{{ __('documents.award.category') }}</td><td>{{ $auction->category?->name }}</td></tr>
            <tr><td class="k">{{ __('documents.award.condition') }}</td><td>{{ $auction->condition?->value }}</td></tr>
            <tr><td class="k">{{ __('documents.award.unit_count') }}</td><td>{{ $auction->unit_count }}</td></tr>
            <tr><td class="k">{{ __('documents.award.location') }}</td><td>{{ $auction->asset_location }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>{{ __('documents.award.auction_section') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('documents.award.auction_id') }}</td><td>{{ $auction->id }}</td></tr>
            <tr><td class="k">{{ __('documents.award.entity') }}</td><td>{{ $auction->entity?->name }}</td></tr>
            <tr><td class="k">{{ __('documents.award.closed_at') }}</td><td>{{ optional($auction->closed_at)->format('Y-m-d H:i') }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>{{ __('documents.award.financial_section') }}</h3>
        <table class="fees">
            @foreach($fees->lines() as $line)
                <tr class="{{ $line['key'] === 'fees.line.buyer_total' ? 'total' : '' }}">
                    <td>{{ __($line['key']) }}</td>
                    <td style="text-align:end">{{ dzd($line['amount']) }}</td>
                </tr>
            @endforeach
            @if($fees->customsImmediateDue !== null)
                <tr><td>{{ __('fees.line.customs_immediate') }}</td><td style="text-align:end">{{ dzd($fees->customsImmediateDue) }}</td></tr>
            @endif
        </table>
    </div>

    <div class="section">
        <h3>{{ __('documents.award.delivery_section') }}</h3>
        <p style="font-size:11px">{{ __('documents.award.delivery_note', ['days' => $auction->finalPaymentDeadlineDays()]) }}</p>
    </div>

    <div class="section">
        <h3>{{ __('documents.award.terms') }}</h3>
        {{-- Admin-authored award clauses (newlines → line breaks) when present;
             otherwise the platform's default wording. --}}
        @if($terms = $auction->localizedAwardTerms())
            <p style="font-size:11px">{!! nl2br(e($terms)) !!}</p>
        @else
            <p style="font-size:11px">{{ __('documents.award.terms_body') }}</p>
        @endif
    </div>

    <div class="legal">{{ __('documents.award.legal_notice') }}</div>

    <div class="section" style="margin-top:18px">
        <span class="stamp">{{ __('documents.common.electronic_signature') }}</span>
    </div>
@endsection
