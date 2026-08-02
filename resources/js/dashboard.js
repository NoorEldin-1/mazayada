/* =============================================================
   Mazayada — Dashboard bundle JS (admin + citizen ONLY)
   Loaded exclusively from the dashboard layouts.
   ============================================================= */

// Preline UI — auto-inits all components on DOMContentLoaded.
// Mazayada does full page reloads (no SPA navigation), so the basic
// import is sufficient. After dynamically injecting Preline markup,
// call window.HSStaticMethods.autoInit() to (re)initialise it.
import 'preline';

/* -------------------------------------------------------------
   Light / dark theme toggle.
   The initial theme is already applied server-side via
   <html data-theme="..."> (read from the `theme` cookie in Blade),
   so there is no flash. Here we only handle the toggle button and
   persist the choice back to the cookie.
   ------------------------------------------------------------- */
function syncThemeControls(t) {
    // Segmented light/dark control(s) in the account menu.
    document.querySelectorAll('[data-theme-set]').forEach((btn) => {
        const on = btn.dataset.themeSet === t;
        btn.classList.toggle('is-on', on);
        btn.setAttribute('aria-pressed', String(on));
    });
}

function applyTheme(theme) {
    const t = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.theme = t;
    document.cookie = 'theme=' + t + '; path=/; max-age=31536000; samesite=lax';
    syncThemeControls(t);
    // Let listeners (e.g. ApexCharts) re-theme themselves.
    window.dispatchEvent(new CustomEvent('themechange', { detail: { theme: t } }));
}

document.addEventListener('click', (e) => {
    // Segmented control — pick an explicit theme (keeps the menu open).
    const setter = e.target.closest('[data-theme-set]');
    if (setter) {
        e.preventDefault();
        applyTheme(setter.dataset.themeSet);
        return;
    }
    // Legacy single toggle (kept for backward compatibility).
    const toggle = e.target.closest('[data-theme-toggle]');
    if (!toggle) return;
    e.preventDefault();
    const current = document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
    applyTheme(current === 'dark' ? 'light' : 'dark');
});

// Expose for any inline/edge use.
window.MazayadaTheme = { apply: applyTheme };

/* -------------------------------------------------------------
   Mobile sidebar drawer (admin + citizen shells).
   [data-drawer-toggle] flips html.drawer-open; clicking the
   backdrop or a [data-drawer-close] element closes it. RTL-safe
   slide direction is handled in dashboard.css.
   ------------------------------------------------------------- */
document.addEventListener('click', (e) => {
    if (e.target.closest('[data-drawer-toggle]')) {
        document.documentElement.classList.toggle('drawer-open');
        return;
    }
    if (e.target.closest('[data-drawer-close]')) {
        document.documentElement.classList.remove('drawer-open');
    }
});

// Close the drawer when leaving mobile width.
window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        document.documentElement.classList.remove('drawer-open');
    }
});

/* -------------------------------------------------------------
   Collapsible sidebar (desktop). [data-sidebar-toggle] flips
   html.sidebar-collapsed and persists it to the `sidebar` cookie
   (mirrors the theme cookie; read server-side so there's no flash).
   The rail's icon-only tooltips are handled by the module below.
   ------------------------------------------------------------- */
document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-sidebar-toggle]');
    if (!toggle) return;
    e.preventDefault();
    const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
    document.cookie = 'sidebar=' + (collapsed ? 'collapsed' : 'expanded') + '; path=/; max-age=31536000; samesite=lax';
    const label = collapsed ? toggle.dataset.labelExpand : toggle.dataset.labelCollapse;
    if (label) { toggle.setAttribute('aria-label', label); toggle.setAttribute('title', label); }
    if (window.hideRailTip) window.hideRailTip();
});

/* -------------------------------------------------------------
   Rail tooltips — shown only while the sidebar is collapsed on
   desktop. A single fixed .rail-tip element is positioned from the
   hovered/focused nav link, so it escapes the nav's overflow clip.
   Label text is read from the link's classless label span (falling
   back to title / aria-label). RTL-aware (tip flips to the start).
   ------------------------------------------------------------- */
(function () {
    let tip = null;
    let current = null;

    function collapsedRail() {
        return window.innerWidth >= 1024
            && document.documentElement.classList.contains('sidebar-collapsed');
    }
    function labelFor(a) {
        const span = a.querySelector('span:not([class])');
        return (span && span.textContent.trim())
            || a.getAttribute('title') || a.getAttribute('aria-label') || '';
    }
    function show(a) {
        const text = labelFor(a);
        if (!text) return;
        if (!tip) { tip = document.createElement('div'); tip.className = 'rail-tip'; document.body.appendChild(tip); }
        tip.textContent = text;
        tip.classList.add('is-visible'); // reveal first so offsetWidth is measurable
        const r = a.getBoundingClientRect();
        const gap = 10;
        tip.style.top = Math.round(r.top + r.height / 2) + 'px';
        tip.style.left = document.documentElement.dir === 'rtl'
            ? Math.round(r.left - gap - tip.offsetWidth) + 'px'
            : Math.round(r.right + gap) + 'px';
    }
    function hide() { current = null; if (tip) tip.classList.remove('is-visible'); }
    window.hideRailTip = hide;

    document.addEventListener('mouseover', (e) => {
        if (!collapsedRail()) return;
        const a = e.target.closest('.dash-side nav a');
        if (a && a !== current) { current = a; show(a); }
        else if (!a && current) { hide(); }
    });
    document.addEventListener('focusin', (e) => {
        if (!collapsedRail()) return;
        const a = e.target.closest('.dash-side nav a');
        if (a) { current = a; show(a); } else { hide(); }
    });
    window.addEventListener('scroll', hide, true);
    window.addEventListener('resize', hide);
})();

/* -------------------------------------------------------------
   Keep the active nav item in view. With a long sidebar, the
   current page's link can sit below the fold; after each full-page
   navigation we centre it inside the scrollable nav (without moving
   the window — we adjust the nav's own scrollTop only).
   ------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
    const active = document.querySelector('.dash-side nav a[aria-current="page"]');
    const nav = active && active.closest('nav');
    if (!nav) return;
    const a = active.getBoundingClientRect();
    const n = nav.getBoundingClientRect();
    if (a.top >= n.top && a.bottom <= n.bottom) return; // already fully visible
    nav.scrollTop += (a.top - n.top) - (n.height - a.height) / 2;
});

/* -------------------------------------------------------------
   ApexCharts — lazy loaded, theme + RTL aware.
   Markup contract (no business logic in Blade):
     <div data-chart data-chart-type="bar" data-chart-horizontal="true">
       <div data-chart-target></div>
       <script type="application/json">{ "categories": [...], "series": [...] }</script>
     </div>
   Re-renders on theme change so colours track the palette.
   ------------------------------------------------------------- */
let chartInstances = [];

async function renderCharts() {
    const nodes = document.querySelectorAll('[data-chart]');
    if (!nodes.length) return;

    const { default: ApexCharts } = await import('apexcharts');

    chartInstances.forEach((c) => { try { c.destroy(); } catch (e) { /* noop */ } });
    chartInstances = [];

    const css = getComputedStyle(document.documentElement);
    const v = (n) => css.getPropertyValue(n).trim();
    const dark = document.documentElement.dataset.theme === 'dark';
    const rtl = document.documentElement.dir === 'rtl';

    const ink = v('--ink'), line = v('--line'), muted = v('--muted'), surface = v('--surface');
    const palette = [v('--primary'), v('--accent'), v('--info'), v('--ok'), v('--primary-4'), v('--warn')];

    nodes.forEach((node) => {
        const target = node.querySelector('[data-chart-target]') || node;
        const json = node.querySelector('script[type="application/json"]');
        if (!json) return;

        let data;
        try { data = JSON.parse(json.textContent); } catch (e) { return; }

        const type = node.dataset.chartType || 'bar';
        const horizontal = node.dataset.chartHorizontal === 'true';
        const height = parseInt(node.dataset.chartHeight || '300', 10);

        const common = {
            chart: { type, height, fontFamily: 'inherit', foreColor: muted, toolbar: { show: false } },
            colors: palette,
            grid: { borderColor: line, strokeDashArray: 4 },
            tooltip: { theme: dark ? 'dark' : 'light' },
            dataLabels: { enabled: false },
            legend: { labels: { colors: ink } },
            stroke: { width: type === 'line' || type === 'area' ? 3 : 0 },
        };

        let options;
        if (type === 'donut' || type === 'pie') {
            options = {
                ...common,
                series: data.series,
                labels: data.labels || [],
                stroke: { colors: [surface], width: 2 },
            };
        } else {
            options = {
                ...common,
                series: data.series,
                plotOptions: { bar: { horizontal, borderRadius: 6, columnWidth: '55%', barHeight: '62%' } },
                xaxis: {
                    categories: data.categories || [],
                    labels: { style: { colors: muted } },
                    axisBorder: { color: line },
                    axisTicks: { color: line },
                },
                yaxis: { labels: { style: { colors: muted } }, opposite: rtl },
            };
        }

        const chart = new ApexCharts(target, options);
        chart.render();
        chartInstances.push(chart);
    });
}

document.addEventListener('DOMContentLoaded', renderCharts);
window.addEventListener('themechange', renderCharts);

/* -------------------------------------------------------------
   Row-action dropdown (⋮) — <x-ui.action-menu>.
   The panel is position:fixed and positioned from the trigger
   rect so it escapes the table's overflow-x clip. One menu open
   at a time; closes on outside-click, Escape, scroll, resize, or
   when an item is activated. RTL/LTR aware; flips above when it
   would overflow the viewport bottom (matters for the last rows).
   ------------------------------------------------------------- */
(function () {
    let openState = null; // { menu, trigger, panel }

    function closeMenu() {
        if (!openState) return;
        openState.trigger.setAttribute('aria-expanded', 'false');
        openState.panel.hidden = true;
        openState.panel.style.left = '';
        openState.panel.style.top = '';
        openState = null;
    }

    function position(trigger, panel) {
        const rtl = document.documentElement.dir === 'rtl';
        const r = trigger.getBoundingClientRect();
        const pw = panel.offsetWidth;
        const ph = panel.offsetHeight;
        const vw = document.documentElement.clientWidth;
        const vh = document.documentElement.clientHeight;
        const gap = 6, pad = 8;

        // Horizontal: align the panel to the trigger on the reading side,
        // then clamp into the viewport.
        let left = rtl ? r.left : (r.right - pw);
        left = Math.max(pad, Math.min(left, vw - pw - pad));

        // Vertical: below the trigger; flip above when it would overflow.
        let top = r.bottom + gap;
        if (top + ph > vh - pad && r.top - gap - ph > pad) {
            top = r.top - gap - ph;
        }
        top = Math.max(pad, Math.min(top, vh - ph - pad));

        panel.style.left = Math.round(left) + 'px';
        panel.style.top = Math.round(top) + 'px';
    }

    function openMenu(menu) {
        const trigger = menu.querySelector('[data-act-trigger]');
        // The panel may have been portaled to <body> on a previous open, so
        // resolve it via aria-controls rather than assuming it's a descendant.
        const panel = menu.querySelector('[data-act-panel]')
            || (trigger && document.getElementById(trigger.getAttribute('aria-controls')));
        if (!trigger || !panel) return;
        closeMenu();
        // If the menu lives inside a transformed ancestor (the mobile sidebar
        // drawer uses translateX), position:fixed would resolve against that
        // ancestor, not the viewport — so the panel lands in the wrong spot.
        // Opt-in [data-act-portal] menus move their panel to <body> on open.
        if (menu.hasAttribute('data-act-portal') && panel.parentElement !== document.body) {
            document.body.appendChild(panel);
        }
        panel.hidden = false; // reveal so it can be measured
        trigger.setAttribute('aria-expanded', 'true');
        openState = { menu, trigger, panel };
        position(trigger, panel);
        const first = panel.querySelector('[role="menuitem"]');
        if (first) first.focus();
    }

    document.addEventListener('click', (e) => {
        // A collapsible sub-group toggle — expand/collapse in place, keep the
        // panel open, and reposition since its height changed. Handled BEFORE the
        // menuitem auto-close so the panel doesn't snap shut.
        const subToggle = e.target.closest('[data-act-subtoggle]');
        if (subToggle) {
            e.preventDefault();
            const sub = subToggle.closest('[data-act-sub]');
            const body = sub && sub.querySelector('[data-act-sub-body]');
            const expanded = subToggle.getAttribute('aria-expanded') === 'true';
            subToggle.setAttribute('aria-expanded', String(!expanded));
            if (body) body.hidden = expanded;
            if (openState) position(openState.trigger, openState.panel);
            return;
        }
        const trigger = e.target.closest('[data-act-trigger]');
        if (trigger) {
            e.preventDefault();
            const menu = trigger.closest('[data-act-menu]');
            if (openState && openState.menu === menu) closeMenu();
            else openMenu(menu);
            return;
        }
        // An item was activated → let its action run (submit / navigate /
        // open modal via a separate listener), then close the menu.
        if (openState && e.target.closest('[data-act-panel] [role="menuitem"]')) {
            closeMenu();
            return;
        }
        // Clicked outside any open menu. Also treat clicks inside the panel
        // itself as "inside" — a portaled panel lives outside [data-act-menu],
        // and non-menuitem controls in it (theme / language) must keep it open.
        if (openState && !e.target.closest('[data-act-menu]') && !e.target.closest('[data-act-panel]')) closeMenu();
    });

    document.addEventListener('keydown', (e) => {
        if (!openState) return;
        if (e.key === 'Escape') {
            const t = openState.trigger;
            closeMenu();
            t.focus();
            return;
        }
        if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(e.key)) return;
        const items = Array.from(openState.panel.querySelectorAll('[role="menuitem"]'));
        if (!items.length) return;
        e.preventDefault();
        const idx = items.indexOf(document.activeElement);
        let next;
        if (e.key === 'Home') next = 0;
        else if (e.key === 'End') next = items.length - 1;
        else if (e.key === 'ArrowDown') next = idx < 0 ? 0 : (idx + 1) % items.length;
        else next = idx <= 0 ? items.length - 1 : idx - 1;
        items[next].focus();
    });

    // A fixed panel can't track a moving trigger — close on scroll/resize.
    window.addEventListener('scroll', closeMenu, true);
    window.addEventListener('resize', closeMenu);
})();

/* -------------------------------------------------------------
   Validation summary — <x-ui.flash>.
   Long forms redirect back with every error at once; bring the
   summary into view and move focus to it so the failure is never
   missed halfway down a scrolled page.
   ------------------------------------------------------------- */
(function () {
    const summary = document.querySelector('[data-error-summary]');
    if (!summary) return;
    summary.scrollIntoView({ block: 'center', behavior: 'smooth' });
    summary.focus({ preventScroll: true });
})();

/* -------------------------------------------------------------
   Generic form modal — <x-ui.modal>.
   Opened by any [data-modal-target="#id"]; closed by
   [data-modal-close], backdrop click, or Escape. Many modals may
   exist per page (one per table row); only one opens at a time.
   Reuses the .mdl-overlay backdrop shared with <x-confirm-modal/>.
   ------------------------------------------------------------- */
(function () {
    let lastFocus = null;

    function topModal() {
        return document.querySelector('.mdl-overlay[data-modal].is-open');
    }

    function openModal(modal) {
        if (!modal) return;
        lastFocus = document.activeElement;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        const focusable = modal.querySelector('input:not([type=hidden]), textarea, select, a[href], button:not([data-modal-close])');
        (focusable || modal.querySelector('[data-modal-close]'))?.focus();
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
        lastFocus = null;
    }

    document.addEventListener('click', (e) => {
        const opener = e.target.closest('[data-modal-target]');
        if (opener) {
            e.preventDefault();
            openModal(document.querySelector(opener.getAttribute('data-modal-target')));
            return;
        }
        const closer = e.target.closest('[data-modal-close]');
        if (closer) {
            e.preventDefault();
            closeModal(closer.closest('.mdl-overlay[data-modal]'));
            return;
        }
        if (e.target.matches('.mdl-overlay[data-modal].is-open')) closeModal(e.target);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { closeModal(topModal()); return; }
        if (e.key !== 'Tab') return;
        const modal = topModal();
        if (!modal) return;
        const f = modal.querySelectorAll('a[href], button:not([disabled]), textarea, input:not([type=hidden]), select, [tabindex]:not([tabindex="-1"])');
        if (!f.length) return;
        const first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });

    /* Never let a modal form fail silently. Relying on the browser's native
       `required` bubble is not enough: inside a modal some browsers refuse to
       submit without ever painting the bubble, so the button reads as inert.
       We render our own message next to the offending field. */
    function fieldError(field, message) {
        let note = field.parentElement?.querySelector('[data-field-error]');
        if (!note) {
            note = document.createElement('small');
            note.setAttribute('data-field-error', '');
            note.className = 'text-danger text-xs mt-1 block';
            field.insertAdjacentElement('afterend', note);
        }
        note.textContent = message;
    }

    function clearFieldErrors(form) {
        form.querySelectorAll('[data-field-error]').forEach((n) => n.remove());
    }

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.closest('.mdl-overlay[data-modal]')) return;

        clearFieldErrors(form);
        if (form.checkValidity()) return;

        e.preventDefault();
        let firstInvalid = null;
        form.querySelectorAll('input, textarea, select').forEach((field) => {
            if (field.willValidate && !field.checkValidity()) {
                fieldError(field, field.validationMessage);
                firstInvalid = firstInvalid || field;
            }
        });
        firstInvalid?.focus();
    });

    /* Re-open the modal a failed submission came from (session('open_modal')),
       so its server-side validation errors are visible instead of disappearing
       with the modal on redirect. */
    const reopen = document.body.dataset.openModal;
    if (reopen) openModal(document.getElementById(reopen));
})();
