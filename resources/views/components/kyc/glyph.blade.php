@props(['status'])
{{--
    Inner KYC status glyph — the white icon drawn on top of a coloured
    fill. Shared by both the seal (<x-kyc-status-badge>) and the labelled
    pill (<x-kyc-status-pill>) so the icon set lives in ONE place.

    Draws with stroke="currentColor"; the host sets color:#fff on the <svg>.
--}}
<g fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
    @switch($status)
        @case(\App\Enums\KycStatus::COMPLETE)
            <path d="m8.4 12.3 2.5 2.5 4.7-5.4"/>
            @break
        @case(\App\Enums\KycStatus::UNDER_REVIEW)
            <path d="M12 8.2v4l2.6 1.6"/>
            @break
        @case(\App\Enums\KycStatus::REJECTED)
            <path d="m9 9 6 6M15 9l-6 6"/>
            @break
        @case(\App\Enums\KycStatus::SUSPENDED)
            <path d="M10 8.6v6.8M14 8.6v6.8"/>
            @break
        @default
            {{-- PENDING / not yet verified --}}
            <path d="M8.3 12h7.4"/>
    @endswitch
</g>
