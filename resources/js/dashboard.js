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
function applyTheme(theme) {
    const t = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.theme = t;
    document.cookie = 'theme=' + t + '; path=/; max-age=31536000; samesite=lax';
    // Let listeners (e.g. ApexCharts) re-theme themselves.
    window.dispatchEvent(new CustomEvent('themechange', { detail: { theme: t } }));
}

document.addEventListener('click', (e) => {
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
        const panel = menu.querySelector('[data-act-panel]');
        if (!trigger || !panel) return;
        closeMenu();
        panel.hidden = false; // reveal so it can be measured
        trigger.setAttribute('aria-expanded', 'true');
        openState = { menu, trigger, panel };
        position(trigger, panel);
        const first = panel.querySelector('[role="menuitem"]');
        if (first) first.focus();
    }

    document.addEventListener('click', (e) => {
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
        // Clicked outside any open menu.
        if (openState && !e.target.closest('[data-act-menu]')) closeMenu();
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
})();
