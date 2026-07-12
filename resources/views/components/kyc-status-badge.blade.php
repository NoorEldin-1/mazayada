{{--
    Premium KYC verification seal — the scalloped "verified" mark shown next
    to the user's name (citizen sidebar + account dropdown). Tone follows the
    status; the COMPLETE (موثّق) seal is the hero — brand-green with a soft
    glow, reading like the verified marks on major platforms. Other statuses
    reuse the same seal shape in a calmer tone (review = blue, rejected /
    suspended = red, pending = neutral grey — deliberately understated so
    "not yet verified" never shouts).

    Icon set is shared with <x-kyc-status-pill> via <x-kyc.glyph>.
    Usage:  <x-kyc-status-badge />
--}}
@auth
@php
    $st = auth()->user()->kyc_status;
    $tone = match ($st) {
        \App\Enums\KycStatus::COMPLETE => 'ok',
        \App\Enums\KycStatus::UNDER_REVIEW => 'info',
        \App\Enums\KycStatus::REJECTED, \App\Enums\KycStatus::SUSPENDED => 'danger',
        default => 'muted',
    };
@endphp
<span {{ $attributes->merge(['class' => "kyc-seal kyc-seal--{$tone}"]) }}
      role="img" title="{{ $st->label() }}" aria-label="{{ $st->label() }}">
    <svg class="kyc-seal__svg" viewBox="0 0 24 24" aria-hidden="true">
        <path class="kyc-seal__disc" d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/>
        <x-kyc.glyph :status="$st" />
    </svg>
</span>
@endauth
