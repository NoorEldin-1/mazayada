{{--
    Premium KYC status pill — the labelled account-verification chip shown
    under the user's name (citizen sidebar + account dropdown). Same premium
    treatment as the merchant gold badge, colour-coded per status:

      • COMPLETE (موثّق)      — solid brand-green, white text, gloss sweep + glow (hero)
      • UNDER_REVIEW (قيد المراجعة) — solid blue (info)
      • REJECTED / SUSPENDED  — solid red (danger)
      • PENDING (not verified) — soft neutral pill, muted text (understated)

    Icon set is shared with <x-kyc-status-badge> via <x-kyc.glyph>.
    Usage:  <x-kyc-status-pill />
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
<span {{ $attributes->merge(['class' => "status-pill status-pill--{$tone}"]) }}>
    <svg class="status-pill__ico" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <x-kyc.glyph :status="$st" />
    </svg>
    <span class="status-pill__text">{{ $st->label() }}</span>
</span>
@endauth
