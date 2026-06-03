@auth
@php
    $u = auth()->user();
@endphp
{{-- Persistent KYC reminder on every citizen page (spec §3.3), except the KYC
     page itself where the detailed status is already shown. --}}
@if($u->kyc_status !== \App\Enums\KycStatus::COMPLETE && ! request()->routeIs('citizen.kyc'))
    @php
        [$bg, $fg, $msg] = match ($u->kyc_status) {
            \App\Enums\KycStatus::UNDER_REVIEW => ['#E0EBF7', '#27568A', __('kyc.banner_under_review_title')],
            \App\Enums\KycStatus::REJECTED     => ['#FBE2E0', '#8E2F2A', __('kyc.banner_rejected_title')],
            \App\Enums\KycStatus::SUSPENDED    => ['#FBE2E0', '#8E2F2A', __('kyc.banner_suspended_title')],
            default                 => ['#FBEFD6', '#8A6310', __('kyc.banner_pending_text')],
        };
    @endphp
    <a href="{{ route('citizen.kyc') }}"
       style="display:flex;align-items:center;gap:10px;background:{{ $bg }};color:{{ $fg }};padding:12px 18px;border-radius:12px;margin-bottom:20px;font-size:13px;font-weight:500;text-decoration:none">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <span style="flex:1">{{ $msg }}</span>
        <span style="font-weight:600;white-space:nowrap">{{ __('kyc.banner_cta') }} ←</span>
    </a>
@endif
@endauth
