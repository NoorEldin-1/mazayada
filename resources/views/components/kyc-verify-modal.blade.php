{{--
    Forced KYC verification modal (citizen dashboard only).

    Shown to citizens who can still act on their verification — i.e. not yet
    verified and not under review (PENDING) or rejected (REJECTED) — via
    User::kycCanSubmit(). It auto-opens on every page load and is intentionally
    NON-dismissible from outside: no backdrop-click and no Escape handler, so the
    user must pick an action. "Verify" navigates to the KYC flow; "Later" only
    closes it (and it reappears on the next visit/refresh).

    Self-contained (inline style + script), modelled on <x-confirm-modal /> — no
    changes to dashboard.js and no Vite rebuild needed for its behaviour. Colours
    use theme variables so it works in both light and dark mode.
--}}
@auth
@php
    $u = auth()->user();
@endphp
@if($u->kycCanSubmit())
<div class="kvm-overlay" id="kycVerifyModal" role="dialog" aria-modal="true" aria-labelledby="kvmTitle" aria-hidden="true">
    <div class="kvm-box">
        <div class="kvm-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <h3 class="kvm-title" id="kvmTitle">{{ __('dashboard.verify_modal_title') }}</h3>
        <p class="kvm-msg">
            {{ $u->isKycRejected() ? __('dashboard.verify_modal_body_rejected') : __('dashboard.verify_modal_body_pending') }}
        </p>
        <div class="kvm-actions">
            <button type="button" class="kvm-btn kvm-cancel" data-kvm-close>{{ __('dashboard.verify_modal_dismiss') }}</button>
            <a href="{{ route('citizen.kyc') }}" class="kvm-btn kvm-primary">{{ __('dashboard.verify_modal_confirm') }}</a>
        </div>
    </div>
</div>

<style>
    .kvm-overlay{position:fixed;inset:0;background:rgba(15,23,42,.6);display:none;align-items:center;justify-content:center;z-index:90;padding:20px}
    .kvm-overlay.open{display:flex}
    .kvm-box{background:var(--surface,#fff);border:1px solid var(--line,#e5e7eb);border-radius:18px;max-width:430px;width:100%;padding:28px;box-shadow:0 24px 70px rgba(0,0,0,.35);text-align:center;animation:kvmpop .14s ease-out}
    @keyframes kvmpop{from{transform:scale(.95);opacity:.3}to{transform:scale(1);opacity:1}}
    .kvm-icon{width:60px;height:60px;border-radius:50%;background:#FBE2E0;color:#8E2F2A;display:grid;place-items:center;margin:0 auto 18px}
    html[data-theme="dark"] .kvm-icon{background:color-mix(in oklab,var(--danger,#D9544E) 22%,transparent);color:#F2ABA5}
    .kvm-title{font-size:19px;font-weight:700;margin:0 0 10px;color:var(--ink,#1a1a1a)}
    .kvm-msg{font-size:14px;color:var(--muted,#667085);margin:0 0 24px;line-height:1.8}
    .kvm-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
    .kvm-btn{min-width:140px;padding:12px 20px;border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;border:0;line-height:1;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;transition:filter .15s,background .15s}
    .kvm-cancel{background:var(--bg-2,#EEF1F4);color:var(--ink-2,#3a3f4a)}
    .kvm-cancel:hover{filter:brightness(.97)}
    .kvm-primary{background:var(--danger,#D9544E);color:#fff}
    .kvm-primary:hover{filter:brightness(1.06)}
</style>

<script>
(function () {
    var modal = document.getElementById('kycVerifyModal');
    if (!modal) return;

    function open() {
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        modal.classList.remove('open');
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
