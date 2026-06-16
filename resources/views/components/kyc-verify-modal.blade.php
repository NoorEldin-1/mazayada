{{--
    Forced KYC verification modal (citizen dashboard only).

    Shown to citizens who can still act on their verification — i.e. not yet
    verified and not under review (PENDING) or rejected (REJECTED) — via
    User::kycCanSubmit(). It auto-opens on every page load and is intentionally
    NON-dismissible from outside: no backdrop-click and no Escape handler, so the
    user must pick an action. "Verify" navigates to the KYC flow; "Later" only
    closes it (and it reappears on the next visit/refresh).

    Uses the shared unified .mdl-* design system (resources/css/dashboard.css) —
    same look as <x-confirm-modal/>, correct in light & dark. Icon tint is brand
    (positive CTA) for pending, danger for a rejected submission.
--}}
@auth
@php
    $u = auth()->user();
@endphp
@if($u->kycCanSubmit())
<div class="mdl-overlay" id="kycVerifyModal" role="dialog" aria-modal="true" aria-labelledby="kvmTitle" aria-hidden="true">
    <div class="mdl-box {{ $u->isKycRejected() ? 'mdl-box--danger' : 'mdl-box--brand' }}">
        <div class="mdl-icon" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <h3 class="mdl-title" id="kvmTitle">{{ __('dashboard.verify_modal_title') }}</h3>
        <p class="mdl-msg">
            {{ $u->isKycRejected() ? __('dashboard.verify_modal_body_rejected') : __('dashboard.verify_modal_body_pending') }}
        </p>
        {{-- Primary action first in DOM → renders at the inline-start (right in RTL, left in LTR). --}}
        <div class="mdl-actions">
            <a href="{{ route('citizen.kyc') }}" class="mdl-btn mdl-btn--primary">{{ __('dashboard.verify_modal_confirm') }}</a>
            <button type="button" class="mdl-btn mdl-btn--ghost" data-kvm-close>{{ __('dashboard.verify_modal_dismiss') }}</button>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('kycVerifyModal');
    if (!modal) return;

    function open() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    // Only the explicit dismiss button closes it — NO backdrop / Escape close.
    modal.querySelectorAll('[data-kvm-close]').forEach(function (b) {
        b.addEventListener('click', close);
    });

    // Auto-open on every load (no suppression — reappears after refresh/return).
    if (document.readyState !== 'loading') open();
    else document.addEventListener('DOMContentLoaded', open);
})();
</script>
@endif
@endauth
