@extends('documents._layout')

@section('doc-content')
    {{-- Delivery report (محضر التسليم) — §4 step 9. --}}
    <div class="section">
        <h3>{{ __('documents.delivery_report.section') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('documents.award.asset_title') }}</td><td>{{ $delivery->auction?->localizedTitle() }}</td></tr>
            <tr><td class="k">{{ __('documents.delivery_report.recipient') }}</td><td>{{ $delivery->user?->fullNameAr() }}</td></tr>
            <tr><td class="k">{{ __('documents.delivery_report.scheduled_at') }}</td><td>{{ optional($delivery->scheduled_at)->format('Y-m-d H:i') }}</td></tr>
            <tr><td class="k">{{ __('documents.delivery_report.delivered_at') }}</td><td>{{ optional($delivery->delivered_at)->format('Y-m-d H:i') }}</td></tr>
            <tr><td class="k">{{ __('documents.delivery_report.address') }}</td><td>{{ $delivery->address }}</td></tr>
            <tr><td class="k">{{ __('documents.delivery_report.status') }}</td><td>{{ $delivery->status->label() }}</td></tr>
        </table>
        @if($delivery->notes)
            <p style="font-size:11px;margin-top:8px">{{ $delivery->notes }}</p>
        @endif
    </div>
@endsection
