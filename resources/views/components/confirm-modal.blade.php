{{--
    Reusable confirmation modal — replaces the native browser confirm() dialog.

    Usage: add `data-confirm="message"` to any <form>. Optional attributes:
      data-confirm-title="..."     custom heading (defaults to common.confirm_title)
      data-confirm-label="..."     custom OK button label (defaults to common.confirm)
      data-confirm-variant="danger" red OK button for destructive actions

    The form submits normally only after the user presses OK.
--}}
<div class="cm-overlay" id="confirmModal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="cm-box" data-cm-box>
        <div class="cm-icon" data-cm-icon>
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <h3 class="cm-title" data-cm-title data-default="{{ __('common.confirm_title') }}">{{ __('common.confirm_title') }}</h3>
        <p class="cm-msg" data-cm-message></p>
        <div class="cm-actions">
            <button type="button" class="btn cm-cancel" data-cm-cancel>{{ __('common.cancel') }}</button>
            <button type="button" class="btn cm-primary" data-cm-ok data-default="{{ __('common.confirm') }}">{{ __('common.confirm') }}</button>
        </div>
    </div>
</div>

<style>
    .cm-overlay{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;z-index:80;padding:20px}
    .cm-overlay.open{display:flex}
    .cm-box{background:var(--surface,#fff);border-radius:18px;max-width:420px;width:100%;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,.25);text-align:center;animation:cmpop .12s ease-out}
    @keyframes cmpop{from{transform:scale(.96);opacity:.4}to{transform:scale(1);opacity:1}}
    .cm-icon{width:54px;height:54px;border-radius:50%;background:#FBEFD6;color:#8A6310;display:grid;place-items:center;margin:0 auto 16px}
    .cm-box.cm-box--danger .cm-icon{background:#FBE2E0;color:#8E2F2A}
    .cm-title{font-size:18px;font-weight:700;margin:0 0 8px;color:var(--ink,#1a1a1a)}
    .cm-msg{font-size:14px;color:var(--muted,#667085);margin:0 0 22px;line-height:1.7}
    .cm-actions{display:flex;gap:12px;justify-content:center}
    .cm-actions .btn{min-width:120px;padding:11px 20px;border-radius:11px;font-size:14px;font-weight:600;cursor:pointer;border:0;line-height:1}
    .cm-cancel{background:#EEF1F4;color:#3a3f4a}
    .cm-cancel:hover{background:#E3E7EC}
    .cm-primary{background:var(--primary,#15573f);color:#fff}
    .cm-primary:hover{filter:brightness(1.06)}
    .cm-danger{background:#ef4444;color:#fff}
    .cm-danger:hover{filter:brightness(1.06)}
</style>

<script>
(function () {
    const modal = document.getElementById('confirmModal');
    if (!modal) return;

    const box = modal.querySelector('[data-cm-box]');
    const titleEl = modal.querySelector('[data-cm-title]');
    const msgEl = modal.querySelector('[data-cm-message]');
    const okBtn = modal.querySelector('[data-cm-ok]');
    const cancelBtn = modal.querySelector('[data-cm-cancel]');
    let pendingForm = null;

    function open(form) {
        pendingForm = form;
        msgEl.textContent = form.dataset.confirm || '';
        titleEl.textContent = form.dataset.confirmTitle || titleEl.dataset.default;
        okBtn.textContent = form.dataset.confirmLabel || okBtn.dataset.default;
        const danger = form.dataset.confirmVariant === 'danger';
        okBtn.className = 'btn ' + (danger ? 'cm-danger' : 'cm-primary');
        box.classList.toggle('cm-box--danger', danger);
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        okBtn.focus();
    }

    function close() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        pendingForm = null;
    }

    // Intercept any form that opts in via data-confirm. HTML5 validation has
    // already passed by the time the submit event fires, so it's safe to defer.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
        if (form.dataset.cmConfirmed === '1') { form.dataset.cmConfirmed = ''; return; }
        e.preventDefault();
        open(form);
    });

    okBtn.addEventListener('click', function () {
        if (!pendingForm) return;
        const f = pendingForm;
        close();
        f.dataset.cmConfirmed = '1';
        if (typeof f.requestSubmit === 'function') f.requestSubmit(); else f.submit();
    });

    cancelBtn.addEventListener('click', close);
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('open')) close();
    });
})();
</script>
