@extends('layouts.app')

@section('title', __('documents.verify.title'))

@section('content')
<div class="container" style="max-width:640px;margin:48px auto;">
    <div class="card card-pad" style="text-align:center;padding:40px 32px">
        @if($valid)
            <div style="width:64px;height:64px;border-radius:50%;background:rgba(21,87,63,.1);color:#15573f;display:grid;place-items:center;margin:0 auto 18px">
                <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <h1 style="font-size:22px;font-weight:700;margin:0 0 8px;color:#15573f">{{ __('documents.verify.valid_title') }}</h1>
            <p style="color:var(--muted);margin:0 0 24px">{{ __('documents.verify.valid_intro') }}</p>

            <table class="kv" style="width:100%;text-align:start;font-size:13px">
                <tr><td style="color:var(--muted);padding:6px">{{ __('documents.verify.doc_type') }}</td><td style="padding:6px">{{ $document->type->label() }}</td></tr>
                <tr><td style="color:var(--muted);padding:6px">{{ __('documents.common.doc_id') }}</td><td style="padding:6px;direction:ltr;text-align:start">{{ $document->id }}</td></tr>
                <tr><td style="color:var(--muted);padding:6px">{{ __('documents.verify.issued_at') }}</td><td style="padding:6px">{{ optional($document->created_at)->format('Y-m-d H:i') }}</td></tr>
                @if($document->auction)
                <tr><td style="color:var(--muted);padding:6px">{{ __('documents.verify.auction') }}</td><td style="padding:6px">{{ $document->auction->localizedTitle() }}</td></tr>
                @if($document->auction->entity)
                <tr><td style="color:var(--muted);padding:6px">{{ __('documents.verify.entity') }}</td><td style="padding:6px">{{ $document->auction->entity->name }}</td></tr>
                @endif
                @endif
                @if(!is_null($amount))
                <tr><td style="color:var(--muted);padding:6px">{{ __('documents.verify.amount') }}</td><td style="padding:6px">{!! dzd_html($amount) !!}</td></tr>
                @endif
                @if($fingerprint)
                <tr><td style="color:var(--muted);padding:6px">{{ __('documents.verify.signature_ref') }}</td><td style="padding:6px;direction:ltr;text-align:start;letter-spacing:.5px">{{ $fingerprint }}</td></tr>
                @endif
            </table>

            <p style="color:var(--muted);font-size:12px;margin:18px 0 0">{{ __('documents.verify.tamper_hint') }}</p>
        @else
            <div style="width:64px;height:64px;border-radius:50%;background:rgba(190,40,40,.1);color:#b91c1c;display:grid;place-items:center;margin:0 auto 18px">
                <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </div>
            <h1 style="font-size:22px;font-weight:700;margin:0 0 8px;color:#b91c1c">{{ __('documents.verify.invalid_title') }}</h1>
            <p style="color:var(--muted);margin:0">{{ __('documents.verify.invalid_intro') }}</p>
        @endif
    </div>
</div>
@endsection
