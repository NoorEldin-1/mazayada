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
