{{--
    Reusable confirmation modal — replaces the native browser confirm() dialog.
    Shares the unified .mdl-* design system (defined in resources/css/dashboard.css)
    with <x-kyc-verify-modal/>, so both modals look identical and theme-correct.

    Usage: add `data-confirm="message"` to any <form>. Optional attributes:
      data-confirm-title="..."      custom heading (defaults to common.confirm_title)
      data-confirm-label="..."      custom OK button label (defaults to common.confirm)
      data-confirm-variant="danger" red icon + red OK button for destructive actions

    The form submits normally only after the user presses OK.
--}}
<div class="mdl-overlay" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="cmTitle" aria-hidden="true">
    <div class="mdl-box" data-cm-box>
        <div class="mdl-icon" data-cm-icon aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <h3 class="mdl-title" id="cmTitle" data-cm-title data-default="{{ __('common.confirm_title') }}">{{ __('common.confirm_title') }}</h3>
        <p class="mdl-msg" data-cm-message></p>
        {{-- Primary action first in DOM → renders at the inline-start (right in RTL, left in LTR). --}}
        <div class="mdl-actions">
            <button type="button" class="mdl-btn mdl-btn--primary" data-cm-ok data-default="{{ __('common.confirm') }}">{{ __('common.confirm') }}</button>
            <button type="button" class="mdl-btn mdl-btn--ghost" data-cm-cancel>{{ __('common.cancel') }}</button>
        </div>
    </div>
</div>

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
    let lastFocus = null;

    function open(form) {
        pendingForm = form;
        lastFocus = document.activeElement;
        msgEl.textContent = form.dataset.confirm || '';
        titleEl.textContent = form.dataset.confirmTitle || titleEl.dataset.default;
        okBtn.textContent = form.dataset.confirmLabel || okBtn.dataset.default;
        const danger = form.dataset.confirmVariant === 'danger';
        okBtn.className = 'mdl-btn ' + (danger ? 'mdl-btn--danger' : 'mdl-btn--primary');
        box.classList.toggle('mdl-box--danger', danger);
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        okBtn.focus();
    }

    function close(restoreFocus = true) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        pendingForm = null;
        if (restoreFocus && lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
        lastFocus = null;
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
        close(false); // navigating away — no need to restore focus to the trigger
        f.dataset.cmConfirmed = '1';
        if (typeof f.requestSubmit === 'function') f.requestSubmit(); else f.submit();
    });

    cancelBtn.addEventListener('click', function () { close(); });
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
    });

    // Minimal focus trap: cycle Tab between the two buttons while open.
    modal.addEventListener('keydown', function (e) {
        if (e.key !== 'Tab' || !modal.classList.contains('is-open')) return;
        const first = okBtn, last = cancelBtn;
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
})();
</script>
