@extends('documents._layout')

@section('doc-content')
    {{-- Payment receipt (إيصال الدفع). --}}
    <div class="section">
        <h3>{{ __('documents.receipt.section') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('documents.receipt.payer') }}</td><td>{{ $payment->user?->fullNameAr() }}</td></tr>
            <tr><td class="k">{{ __('documents.receipt.payment_type') }}</td><td>{{ $payment->payment_type->label() }}</td></tr>
            <tr><td class="k">{{ __('documents.receipt.amount') }}</td><td>{!! dzd_pdf((int) $payment->amount) !!}</td></tr>
            <tr><td class="k">{{ __('documents.receipt.status') }}</td><td>{{ $payment->status->value }}</td></tr>
            <tr><td class="k">{{ __('documents.receipt.reference') }}</td><td>{{ $payment->gateway_ref }}</td></tr>
            <tr><td class="k">{{ __('documents.receipt.confirmed_at') }}</td><td>{{ optional($payment->confirmed_at)->format('Y-m-d H:i') }}</td></tr>
            @if($payment->auction)
            <tr><td class="k">{{ __('documents.award.asset_title') }}</td><td>{{ $payment->auction->localizedTitle() }}</td></tr>
            @endif
        </table>
    </div>

    <div class="section" style="margin-top:18px">
        <span class="stamp">{{ __('documents.common.electronic_signature') }}</span>
    </div>
@endsection
