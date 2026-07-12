@props([
    'compact' => false,
])
{{--
    Verified-merchant badge — a premium gold seal shown for accounts whose
    Commercial Register (السجل التجاري) is admin-approved AND still in date
    (User::hasCommerceRegister()). Self-guarding: renders NOTHING otherwise,
    so it is safe to drop anywhere (it never shows for admins/entities, who
    hold no register).

    Two shapes:
      • default  — full gold pill: seal icon + "تاجر معتمد" label.
      • :compact — icon-only seal chip (for tight inline rows), label kept
                   accessible via title / aria-label.

    Deliberately NOT built on <x-ui.badge>: it uses a gold gradient + shine
    sweep (.cr-badge in dashboard.css) to read as a distinct trust signal,
    a level above the neutral KYC status chip.

    Usage:  <x-commercial-register-badge />
            <x-commercial-register-badge compact />
--}}
@auth
@if(auth()->user()->hasCommerceRegister())
    @php
        $label = __('commercial-register.badge_verified');
        $hint  = __('commercial-register.badge_verified_hint');
    @endphp
    <span {{ $attributes->merge(['class' => 'cr-badge'.($compact ? ' cr-badge--chip' : '')]) }}
          title="{{ $hint }}"
          role="img"
          aria-label="{{ $label }} — {{ $hint }}">
        {{-- Scalloped verification seal + check --}}
        <svg class="cr-badge__seal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/>
            <path d="m9 12 2 2 4-4"/>
        </svg>
        @unless($compact)
            <span class="cr-badge__text">{{ $label }}</span>
        @endunless
    </span>
@endif
@endauth
