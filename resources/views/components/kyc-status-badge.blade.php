{{--
    Compact KYC status badge — an icon chip coloured per the user's verification
    status, shown next to the user's name (citizen sidebar + top bar).
    Tone (bg + text + dark-mode) is reused from <x-ui.badge> via badgeVariant().
--}}
@auth
@php
    $st = auth()->user()->kyc_status;
@endphp
<x-ui.badge :variant="$st->badgeVariant()" class="!px-1.5 !py-1 shrink-0" :title="$st->label()" aria-label="{{ $st->label() }}">
    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        @switch($st)
            @case(\App\Enums\KycStatus::COMPLETE)
                <polyline points="20 6 9 17 4 12"/>
                @break
            @case(\App\Enums\KycStatus::UNDER_REVIEW)
                <circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 16 14"/>
                @break
            @case(\App\Enums\KycStatus::REJECTED)
                <circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                @break
            @case(\App\Enums\KycStatus::SUSPENDED)
                <rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>
                @break
            @default
                {{-- PENDING --}}
                <circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16.5" x2="12.01" y2="16.5"/>
        @endswitch
    </svg>
</x-ui.badge>
@endauth
