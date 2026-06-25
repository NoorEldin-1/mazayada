@extends('documents._layout')

@section('doc-content')
    {{-- §10.3 — Condition book (كراسة الشروط / cahier des charges). --}}
    <div class="section">
        <h3>{{ __('documents.condition_book.asset_section') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('documents.award.asset_title') }}</td><td>{{ $auction->localizedTitle() }}</td></tr>
            <tr><td class="k">{{ __('documents.award.entity') }}</td><td>{{ $auction->entity?->name }}</td></tr>
            <tr><td class="k">{{ __('documents.award.category') }}</td><td>{{ $auction->category?->name }}</td></tr>
            <tr><td class="k">{{ __('documents.condition_book.opening_price') }}</td><td>{!! dzd_pdf((int) $auction->opening_price) !!}</td></tr>
            <tr><td class="k">{{ __('documents.condition_book.deposit') }}</td><td>{!! dzd_pdf((int) $auction->deposit_amount) !!}</td></tr>
            <tr><td class="k">{{ __('documents.condition_book.book_price') }}</td><td>{!! dzd_pdf((int) $auction->book_price) !!}</td></tr>
            <tr><td class="k">{{ __('documents.condition_book.start') }}</td><td>{{ optional($auction->start_time)->format('Y-m-d H:i') }}</td></tr>
        </table>
    </div>

    @if($auction->description_ar)
    <div class="section">
        <h3>{{ __('documents.condition_book.description') }}</h3>
        <p style="font-size:11px">{{ $auction->description_ar }}</p>
    </div>
    @endif

    <div class="section">
        <h3>{{ __('documents.condition_book.terms') }}</h3>
        {{-- Admin-authored terms (newlines → line breaks) when present; otherwise
             the platform's default legal wording. --}}
        @if($terms = $auction->localizedConditionTerms())
            <p style="font-size:11px">{!! nl2br(e($terms)) !!}</p>
        @else
            <p style="font-size:11px">{{ __('documents.condition_book.terms_body') }}</p>
        @endif
        @if($auction->requires_commerce_register)
            <p style="font-size:11px">— {{ __('documents.condition_book.requires_commerce_register') }}</p>
        @endif
        @if($auction->requires_newspaper_announcement)
            <p style="font-size:11px">— {{ __('documents.condition_book.requires_newspaper') }}</p>
        @endif
    </div>

    <div class="section" style="margin-top:18px">
        <span class="stamp">{{ __('documents.common.electronic_signature') }}</span>
    </div>
@endsection
