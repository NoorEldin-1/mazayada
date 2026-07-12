/*
 * Browse page (auctions.index) interactivity:
 *   1. Live search — YouTube-style debounced dropdown that jumps to an auction.
 *   2. Cascading commune select — loads communes for the chosen wilaya.
 *   3. Mobile filters toggle — reveals the sidebar on narrow screens.
 * The advanced-filter accordion uses native <details>, so it needs no JS.
 */
(function () {
    'use strict';

    var esc = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };

    /* ---------------------------------------------------------------- *
     * 1. Live search
     * ---------------------------------------------------------------- */
    (function initSearch() {
        var root = document.querySelector('[data-search]');
        if (!root) return;

        var input = root.querySelector('[data-search-input]');
        var results = root.querySelector('[data-search-results]');
        var clearBtn = root.querySelector('[data-search-clear]');
        var endpoint = root.getAttribute('data-endpoint');
        var labels = {};
        try { labels = JSON.parse(root.getAttribute('data-labels') || '{}'); } catch (e) {}

        var timer = null;
        var controller = null;
        var items = [];      // current result anchors
        var activeIndex = -1;

        function open() { results.hidden = false; input.setAttribute('aria-expanded', 'true'); }
        function close() {
            results.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
        }

        function setState(html) { results.innerHTML = html; open(); }

        function renderMessage(text) {
            setState('<div class="br-search-msg">' + esc(text) + '</div>');
            items = [];
        }

        function render(list) {
            if (!list.length) { renderMessage(labels.no_results || 'No results'); return; }
            var html = list.map(function (r) {
                var thumb = r.thumb
                    ? '<img src="' + esc(r.thumb) + '" alt="" loading="lazy">'
                    : '<span class="br-sr-ph"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 3.5l6 6M3 21l1.5-4.5L17 4a1.41 1.41 0 0 1 2 2L6.5 18.5 3 21z"/></svg></span>';
                var meta = [r.category, r.wilaya].filter(Boolean).map(esc).join(' · ');
                var live = r.live ? '<span class="br-sr-live">' + esc(labels.live || '') + '</span>' : '';
                return '<a href="' + esc(r.url) + '" class="br-sr-item" role="option">' +
                    '<span class="br-sr-thumb">' + thumb + '</span>' +
                    '<span class="br-sr-body">' +
                        '<span class="br-sr-ttl">' + esc(r.title) + '</span>' +
                        '<span class="br-sr-meta">' + meta + '</span>' +
                    '</span>' +
                    '<span class="br-sr-side">' + live + '<span class="br-sr-price">' + (r.price_html || '') + '</span></span>' +
                '</a>';
            }).join('');
            setState(html);
            items = Array.prototype.slice.call(results.querySelectorAll('.br-sr-item'));
        }

        function fetchResults(q) {
            if (controller) controller.abort();
            controller = new AbortController();
            renderMessage(labels.searching || 'Searching…');
            fetch(endpoint + '?q=' + encodeURIComponent(q), {
                signal: controller.signal,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) { render(data.results || []); })
                .catch(function (err) { if (err.name !== 'AbortError') close(); });
        }

        function onInput() {
            var q = input.value.trim();
            clearBtn.hidden = q.length === 0;
            clearTimeout(timer);
            if (q.length < 2) {
                if (q.length === 0) { close(); }
                else { renderMessage(labels.hint || ''); items = []; }
                return;
            }
            timer = setTimeout(function () { fetchResults(q); }, 220);
        }

        function highlight(next) {
            if (!items.length) return;
            if (activeIndex > -1) items[activeIndex].classList.remove('is-active');
            activeIndex = (next + items.length) % items.length;
            items[activeIndex].classList.add('is-active');
            items[activeIndex].scrollIntoView({ block: 'nearest' });
        }

        input.addEventListener('input', onInput);
        input.addEventListener('focus', function () {
            if (input.value.trim().length >= 2 && results.innerHTML) open();
        });
        input.addEventListener('keydown', function (e) {
            if (results.hidden) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); highlight(activeIndex + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(activeIndex - 1); }
            else if (e.key === 'Enter') {
                if (activeIndex > -1 && items[activeIndex]) { e.preventDefault(); window.location = items[activeIndex].href; }
            } else if (e.key === 'Escape') { close(); }
        });

        clearBtn.addEventListener('click', function () {
            input.value = '';
            clearBtn.hidden = true;
            close();
            input.focus();
        });

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) close();
        });
    })();

    /* ---------------------------------------------------------------- *
     * 2. Cascading commune select
     * ---------------------------------------------------------------- */
    (function initCommune() {
        var wilaya = document.querySelector('[data-wilaya]');
        var commune = document.querySelector('[data-commune]');
        if (!wilaya || !commune) return;

        var base = wilaya.getAttribute('data-communes-url');
        var placeholder = wilaya.getAttribute('data-placeholder');
        var allLabel = wilaya.getAttribute('data-all');
        var locale = wilaya.getAttribute('data-locale') || 'ar';

        function reset(text, disabled) {
            commune.innerHTML = '<option value="">' + esc(text) + '</option>';
            commune.disabled = disabled;
        }

        wilaya.addEventListener('change', function () {
            var id = wilaya.value;
            if (!id) { reset(placeholder, true); return; }
            reset(allLabel, true);
            fetch(base + '/' + encodeURIComponent(id) + '/communes', {
                headers: { 'Accept': 'application/json' }
            })
                .then(function (r) { return r.json(); })
                .then(function (list) {
                    var opts = '<option value="">' + esc(allLabel) + '</option>';
                    (list || []).forEach(function (c) {
                        var name = (locale === 'ar' ? c.name_ar : c.name_fr) || c.name_ar;
                        opts += '<option value="' + esc(c.id) + '">' + esc(name) + '</option>';
                    });
                    commune.innerHTML = opts;
                    commune.disabled = false;
                })
                .catch(function () { reset(allLabel, false); });
        });
    })();

    /* ---------------------------------------------------------------- *
     * 3. Mobile filters toggle
     * ---------------------------------------------------------------- */
    (function initMobileToggle() {
        var toggle = document.querySelector('[data-filters-toggle]');
        var side = document.querySelector('[data-br-side]');
        if (!toggle || !side) return;

        toggle.addEventListener('click', function () {
            var open = side.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(open));
            if (open) side.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    })();
})();
