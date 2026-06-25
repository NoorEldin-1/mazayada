// ============================================================================
//  Mazayada — Live Auction client (spec §6: Real-Time Auction Engine)
// ----------------------------------------------------------------------------
//  Loaded ONLY on the public auction detail page (auctions/show.blade.php).
//  Responsibilities:
//    • Subscribe to the public Echo channel `auction.{id}` (Reverb / Pusher
//      protocol) and apply live updates with NO page reload (§6.1, §6.2):
//        - .bid.placed       → current price, bid count, recent-bids list, input min
//        - .auction.extended → push the countdown end forward (§6.3 anti-sniping)
//        - .auction.closed   → reload to render the canonical closed panel (§6.6)
//    • Submit bids over AJAX to the existing JSON endpoint (auctions.bid) and
//      show server validation errors inline (BID_TOO_LOW / NO_DEPOSIT / …).
//    • Drive the countdown timer (server end_time is the source of truth).
//
//  Bidder privacy (§6.5): the UI only ever shows the deterministic alias that
//  the server broadcasts — never a real identity.
//
//  Everything is defensive: if the config blob or a DOM hook is missing the
//  feature degrades gracefully (the server-rendered page still works).
// ============================================================================
import './echo';

(function () {
    const configEl = document.getElementById('auction-realtime-config');
    if (!configEl) return;

    let cfg;
    try {
        cfg = JSON.parse(configEl.textContent);
    } catch (_) {
        return;
    }
    if (!cfg || !cfg.auctionId) return;

    const i18n = cfg.i18n || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Honour the OS "reduce motion" setting: the price roll and row entrance
    // animations fall back to an instant swap when this is on.
    const reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    // Mutable view state — the single source of truth for the live price.
    const state = {
        currentPrice: Number(cfg.currentPrice) || 0,
        endMs: cfg.endTime ? new Date(cfg.endTime).getTime() : null,
    };

    // ── DOM hooks (all optional) ───────────────────────────────────────────
    const priceEl = document.getElementById('liveCurrentPrice');
    const countEl = document.getElementById('liveBidCount');
    const listEl = document.getElementById('liveBidList');
    const historyBody = document.getElementById('liveBidHistory');     // tab "سجل العروض" <tbody>
    const historyCountEl = document.getElementById('liveBidHistoryCount');
    const form = document.getElementById('bidForm');
    const amountInput = document.getElementById('bidAmount');
    const submitBtn = document.getElementById('bidSubmit');
    const errorEl = document.getElementById('bidError');
    const minHintEl = document.getElementById('bidMinHint');
    const panelEl = document.getElementById('bidPanel');
    const quickBtns = document.querySelectorAll('[data-quickbid]');
    const endedNotice = document.getElementById('bidEndedNotice');
    const endedText = document.getElementById('bidEndedText');

    // Lifecycle flags: `locked` = bidding paused because the clock hit zero (may
    // reopen on an anti-sniping extension or a clock-skew false alarm);
    // `closedFinal` = the auction is irreversibly closed (server broadcast) and
    // must never reopen.
    let locked = false;
    let closedFinal = false;

    // ── Helpers ────────────────────────────────────────────────────────────
    // Mirror Blade's <x-money>: number_format(dinars, 0, ',', ' ') → "1 234".
    function formatDinars(centimes) {
        const dinars = Math.trunc(Number(centimes) / 100);
        return dinars.toLocaleString('en-US').replace(/,/g, ' ');
    }

    // Group a whole-dinar integer with space thousands separators — the same
    // separators <x-money> uses, so the bid input matches the prices around it.
    function groupDinars(dinars) {
        return (Math.trunc(Number(dinars)) || 0).toLocaleString('en-US').replace(/,/g, ' ');
    }

    // Pull just the digits the user typed (the field shows spaces for grouping).
    function parseDigits(str) {
        const digits = String(str ?? '').replace(/\D/g, '');
        return digits ? parseInt(digits, 10) : 0;
    }

    // The live current price expressed in dinars (the unit the input speaks).
    function currentPriceDinars() {
        return Math.trunc(state.currentPrice / 100);
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function flash(el) {
        if (!el || typeof el.animate !== 'function') return;
        el.animate([{ opacity: 0.3 }, { opacity: 1 }], { duration: 450, easing: 'ease-out' });
    }

    // Roll the price number: the old value slides up and out while the new value
    // rises from below (slot-machine style). Falls back to an instant swap when
    // motion is reduced or the Web Animations API is unavailable.
    function rollAmount(amtEl, newText) {
        if (!amtEl) return;
        // Track the true displayed value on the element: mid-roll the innerHTML holds
        // two stacked copies, so textContent isn't reliable for back-to-back bids.
        const oldText = amtEl.dataset.value != null ? amtEl.dataset.value : amtEl.textContent;
        amtEl.dataset.value = newText;
        if (oldText === newText) { flash(priceEl); return; }
        // The price roll is the headline live-feedback the page is built around, so it
        // plays whenever the Web Animations API is available — including when the OS
        // has "reduce motion" on (a common Windows default that otherwise silently
        // killed the roll). Only fall back to an instant swap with no WAAPI at all.
        if (typeof amtEl.animate !== 'function') {
            amtEl.classList.remove('is-rolling');
            amtEl.textContent = newText;
            flash(priceEl);
            return;
        }
        amtEl.classList.add('is-rolling');
        amtEl.innerHTML =
            '<span class="roll-old">' + escapeHtml(oldText) + '</span>'
            + '<span class="roll-new">' + escapeHtml(newText) + '</span>';
        const oldSpan = amtEl.querySelector('.roll-old');
        const newSpan = amtEl.querySelector('.roll-new');
        const opts = { duration: 520, easing: 'cubic-bezier(.22,1,.36,1)', fill: 'forwards' };
        if (oldSpan) {
            oldSpan.animate([
                { transform: 'translateY(0)', opacity: 1 },
                { transform: 'translateY(-110%)', opacity: 0 },
            ], opts);
        }
        const a = newSpan && newSpan.animate([
            { transform: 'translateY(110%)', opacity: 0.3 },
            { transform: 'translateY(0)', opacity: 1 },
        ], opts);
        // Flatten back to plain text — but only if no newer roll has superseded us.
        const settle = () => {
            if (amtEl.dataset.value !== newText) return;
            amtEl.classList.remove('is-rolling');
            amtEl.textContent = newText;
        };
        if (a) { a.onfinish = settle; } else { settle(); }
        // Soft gold glow on the whole price to underline the change.
        if (priceEl && typeof priceEl.animate === 'function') {
            priceEl.animate([
                { textShadow: '0 0 0 rgba(212,168,67,0)' },
                { textShadow: '0 0 18px rgba(212,168,67,.55)' },
                { textShadow: '0 0 0 rgba(212,168,67,0)' },
            ], { duration: 700, easing: 'ease-out' });
        }
    }

    // Subtle entrance for a freshly-prepended bid row (fade + slide from the top).
    function animateRowIn(row) {
        if (!row || reduceMotion || typeof row.animate !== 'function') return;
        row.animate([
            { opacity: 0, transform: 'translateY(-8px)' },
            { opacity: 1, transform: 'translateY(0)' },
        ], { duration: 380, easing: 'cubic-bezier(.22,1,.36,1)' });
    }

    // Markup for the premium "highest bid" pill badge (mirrors the Blade version).
    function badgeHtml() {
        return '<span class="top-badge">'
            + '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'
            + '<path d="M5 16 3 6l5.5 4L12 4l3.5 6L21 6l-2 10H5Zm0 3h14v2H5z"/></svg>'
            + escapeHtml(i18n.highest_badge || '') + '</span>';
    }

    let toastTimer = null;
    function toast(message) {
        if (!message) return;
        let box = document.getElementById('liveToast');
        if (!box) {
            box = document.createElement('div');
            box.id = 'liveToast';
            box.style.cssText = 'position:fixed;inset-inline-end:18px;inset-block-end:18px;z-index:9999;'
                + 'background:#1d6045;color:#fff;padding:12px 18px;border-radius:12px;font-size:14px;'
                + 'font-weight:600;box-shadow:0 8px 28px rgba(0,0,0,.25);max-width:320px';
            document.body.appendChild(box);
        }
        box.textContent = message;
        box.style.opacity = '1';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { box.style.transition = 'opacity .4s'; box.style.opacity = '0'; }, 3000);
    }

    // ── Live DOM updates ───────────────────────────────────────────────────
    // Reflect the new minimum next bid (current price + 1 dinar) in the hint.
    function updateMinHint() {
        if (!minHintEl || !i18n.min_bid) return;
        const minNext = currentPriceDinars() + 1;
        // Build the price as the same .money markup the server emits so the CSS
        // places the currency on the document's reading side (AR: left) and keeps
        // the amount a coherent LTR token. The label template is our own trusted
        // translation; only the (server-provided) currency is escaped.
        const priceHtml = '<span class="money"><span class="amt">' + groupDinars(minNext)
            + '</span> <span class="cur">' + escapeHtml(cfg.currency || '') + '</span></span>';
        minHintEl.innerHTML = i18n.min_bid.replace('{price}', priceHtml);
    }

    function setPrice(centimes) {
        state.currentPrice = Number(centimes) || state.currentPrice;
        if (priceEl) {
            const amt = priceEl.querySelector('.amt');
            rollAmount(amt, formatDinars(state.currentPrice));
        }
        if (amountInput) amountInput.dataset.current = String(state.currentPrice);
        updateMinHint();
    }

    function incrementCount() {
        if (!countEl) return;
        const n = parseInt((countEl.textContent || '0').replace(/\D/g, ''), 10) || 0;
        countEl.textContent = String(n + 1);
    }

    // Sidebar "آخر العروض" — prepend a new bid row (class-based markup matching the
    // Blade partial so the premium .is-top styling applies identically).
    function prependBidRow(alias, centimes) {
        if (!listEl) return;
        document.getElementById('liveBidEmpty')?.remove();
        const row = document.createElement('div');
        row.className = 'bid-row';
        const safeAlias = escapeHtml(alias);
        row.innerHTML =
            '<div class="bid-row-av num">' + safeAlias.slice(0, 2) + '</div>'
            + '<div class="bid-row-main">'
            + '<div class="bid-row-name"><span>' + safeAlias + '</span></div>'
            + '<div class="bid-row-time">' + escapeHtml(i18n.now || '') + '</div></div>'
            + '<div class="bid-row-amt"><span class="money"><span class="amt">' + formatDinars(centimes) + '</span> '
            + '<span class="cur">' + escapeHtml(cfg.currency || '') + '</span></span></div>';
        listEl.prepend(row);
        while (listEl.children.length > 10) listEl.lastElementChild.remove();
        animateRowIn(row);
    }

    // Tab "سجل العروض" — prepend a new row into the history table (mirrors the
    // sidebar so both lists update live from the single .bid.placed broadcast).
    function prependHistoryRow(alias, centimes) {
        if (!historyBody) return;
        document.getElementById('liveBidHistoryEmpty')?.remove();
        const tr = document.createElement('tr');
        tr.className = 'bid-hist-row';
        const safeAlias = escapeHtml(alias);
        tr.innerHTML =
            '<td class="bid-hist-bidder"><span>' + safeAlias + '</span></td>'
            + '<td class="bid-hist-amt"><span class="money"><span class="amt">' + formatDinars(centimes) + '</span> '
            + '<span class="cur">' + escapeHtml(cfg.currency || '') + '</span></span></td>'
            + '<td class="bid-hist-time">' + escapeHtml(i18n.now || '') + '</td>';
        historyBody.prepend(tr);
        while (historyBody.children.length > 10) historyBody.lastElementChild.remove();
        updateHistoryCount();
        animateRowIn(tr);
    }

    function updateHistoryCount() {
        if (!historyCountEl || !historyBody) return;
        const n = Array.from(historyBody.children).filter((r) => r.id !== 'liveBidHistoryEmpty').length;
        historyCountEl.textContent = String(Math.min(n, 10));
    }

    // Move the premium "highest bid" highlight to the current top row of a list:
    // mark the first real row .is-top and inject the badge; clear it from the rest.
    function applyTopMarker(container, getBadgeTarget, emptyId) {
        if (!container) return;
        let first = true;
        Array.from(container.children).forEach((row) => {
            if (emptyId && row.id === emptyId) return;
            if (first) {
                first = false;
                row.classList.add('is-top');
                const target = getBadgeTarget(row);
                if (target && !target.querySelector('.top-badge')) {
                    target.insertAdjacentHTML('beforeend', badgeHtml());
                }
            } else {
                row.classList.remove('is-top');
                row.querySelector('.top-badge')?.remove();
            }
        });
    }

    function refreshTopBadges() {
        applyTopMarker(listEl, (row) => row.querySelector('.bid-row-name'), 'liveBidEmpty');
        applyTopMarker(historyBody, (row) => row.querySelector('.bid-hist-bidder') || row.cells?.[0], 'liveBidHistoryEmpty');
    }

    // Single entry point for a new bid: update both lists, then re-anchor the
    // premium highlight to the new top (highest = latest) row.
    function applyNewBid(alias, centimes) {
        prependBidRow(alias, centimes);
        prependHistoryRow(alias, centimes);
        refreshTopBadges();
    }

    // ── Auction lifecycle: lock / unlock / closed result ───────────────────
    // Disable every actionable control the instant the clock runs out. Kept
    // independent of the server close so the UI reacts immediately and never
    // shows a live-looking panel that the server is about to reject.
    function lockBidding(message) {
        if (closedFinal) return;
        locked = true;
        if (panelEl) panelEl.classList.add('is-ended');
        if (submitBtn) submitBtn.disabled = true;
        if (amountInput) amountInput.disabled = true;
        quickBtns.forEach((b) => { b.disabled = true; });
        showError('');
        if (endedNotice) {
            if (endedText && message) endedText.textContent = message;
            endedNotice.hidden = false;
        }
    }

    // Reopen bidding — only ever called for a still-live auction (anti-sniping
    // extension, or a clock-skew false alarm); never after a real close.
    function unlockBidding() {
        if (closedFinal) return;
        locked = false;
        if (panelEl) panelEl.classList.remove('is-ended');
        if (submitBtn) submitBtn.disabled = false;
        if (amountInput) amountInput.disabled = false;
        quickBtns.forEach((b) => { b.disabled = false; });
        if (endedNotice) endedNotice.hidden = true;
    }

    // Irreversible close — render the winner + final price inline from the
    // broadcast payload so there's no flash of the live panel before the reload
    // that fetches the canonical server-rendered closed panel.
    function renderClosed(winnerAlias, finalPrice) {
        closedFinal = true;
        locked = true;
        if (panelEl) panelEl.classList.add('is-ended', 'is-closed');
        if (form) form.style.display = 'none';
        if (submitBtn) submitBtn.disabled = true;
        if (amountInput) amountInput.disabled = true;
        quickBtns.forEach((b) => { b.disabled = true; });
        if (endedNotice) endedNotice.hidden = true;
        const cd = document.getElementById('bidCountdown');
        if (cd) cd.style.display = 'none';

        if (!panelEl || document.getElementById('bidClosedResult')) return;
        const box = document.createElement('div');
        box.id = 'bidClosedResult';
        box.className = 'bid-closed-result';
        let inner = '<div class="bcr-t">' + escapeHtml(i18n.closed_title || '') + '</div>';
        if (winnerAlias) {
            inner += '<div class="bcr-w">' + escapeHtml(i18n.winner_label || '')
                + ' <strong>' + escapeHtml(winnerAlias) + '</strong></div>';
            if (finalPrice != null) {
                inner += '<div class="bcr-p money"><span class="amt">' + formatDinars(finalPrice)
                    + '</span> <span class="cur">' + escapeHtml(cfg.currency || '') + '</span></div>';
            }
        } else {
            inner += '<div class="bcr-w">' + escapeHtml(i18n.no_winner || '') + '</div>';
        }
        box.innerHTML = inner;
        panelEl.appendChild(box);
    }

    // ── Countdown (server end_time is authoritative) ───────────────────────
    (function countdown() {
        const el = document.querySelector('.countdown');
        if (!el) return;
        if (!state.endMs) {
            const ds = el.dataset.end;
            if (ds) state.endMs = new Date(ds).getTime();
        }
        const hEl = document.getElementById('cd-h');
        const mEl = document.getElementById('cd-m');
        const sEl = document.getElementById('cd-s');
        function tick() {
            if (!state.endMs) return;
            const remaining = Math.max(0, Math.floor((state.endMs - Date.now()) / 1000));
            let diff = remaining;
            const h = Math.floor(diff / 3600); diff %= 3600;
            const m = Math.floor(diff / 60);
            const s = diff % 60;
            if (hEl) hEl.textContent = String(h).padStart(2, '0');
            if (mEl) mEl.textContent = String(m).padStart(2, '0');
            if (sEl) sEl.textContent = String(s).padStart(2, '0');

            // Last-minute urgency.
            el.classList.toggle('is-ending', remaining > 0 && remaining <= 60);

            if (remaining <= 0) {
                // Time is up on our clock — lock immediately, no server round-trip.
                if (!locked && !closedFinal) lockBidding(i18n.awaiting_result);
            } else if (locked && !closedFinal) {
                // Time came back (anti-sniping extension, or our clock was ahead) —
                // reopen bidding.
                unlockBidding();
            }
        }
        tick();
        setInterval(tick, 1000);
    })();

    // ── Live thousands grouping (space separators) as the user types ────────
    if (amountInput) {
        amountInput.addEventListener('input', () => {
            const prev = amountInput.value;
            const caret = amountInput.selectionStart ?? prev.length;
            // Count the digits left of the caret so we can restore its position
            // after re-grouping changes the string length.
            const digitsBefore = prev.slice(0, caret).replace(/\D/g, '').length;
            const value = parseDigits(prev);
            const formatted = value ? groupDinars(value) : '';
            amountInput.value = formatted;
            let pos = 0, seen = 0;
            while (pos < formatted.length && seen < digitsBefore) {
                if (/\d/.test(formatted[pos])) seen++;
                pos++;
            }
            try { amountInput.setSelectionRange(pos, pos); } catch (_) { /* unsupported */ }
        });
        updateMinHint();
    }

    // ── Quick-bid buttons (+1 000 / +5 000 / +10 000 …, increments in dinars) ─
    document.querySelectorAll('[data-quickbid]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const inc = parseInt(btn.dataset.quickbid, 10) || 0; // dinars
            if (amountInput) amountInput.value = groupDinars(currentPriceDinars() + inc);
        });
    });

    // ── AJAX bid submission ────────────────────────────────────────────────
    function showError(msg) {
        if (!errorEl) return;
        errorEl.textContent = msg || i18n.error_generic || '';
        errorEl.style.display = msg ? 'block' : 'none';
    }

    if (form && cfg.bidUrl) {
        form.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            showError('');
            // Time's up on our clock — don't even hit the server; lock the panel.
            if (closedFinal || locked || (state.endMs && Date.now() >= state.endMs)) {
                lockBidding(i18n.awaiting_result);
                return;
            }
            const amount = parseDigits(amountInput?.value); // entered in dinars
            if (!amount || amount <= 0) { showError(i18n.invalid_amount); return; }
            if (amount * 100 <= state.currentPrice) { showError(i18n.too_low); return; }

            if (submitBtn) submitBtn.disabled = true;
            try {
                const res = await fetch(cfg.bidUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ amount }),
                });

                if (res.status === 429) { showError(i18n.rate_limited); return; }

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    // The server is authoritative on timing: if it rejected us and
                    // the clock is out, surface the ended state, not a raw error.
                    if (state.endMs && Date.now() >= state.endMs) {
                        lockBidding(i18n.awaiting_result);
                        return;
                    }
                    showError(data.error || data.errors?.amount?.[0] || i18n.error_generic);
                    return;
                }
                // Success — clear the field. Price / count / list refresh via the
                // broadcast (.bid.placed) so every client, including this one,
                // updates through a single code path (no double counting).
                if (amountInput) amountInput.value = '';
            } catch (_) {
                showError(i18n.error_generic);
            } finally {
                // Leave it disabled if the panel locked/closed while we waited.
                if (submitBtn && !locked && !closedFinal) submitBtn.disabled = false;
            }
        });
    }

    // ── Echo subscription ──────────────────────────────────────────────────
    if (!window.Echo) return;

    window.Echo.channel('auction.' + cfg.auctionId)
        .listen('.bid.placed', (e) => {
            // A fresh valid bid proves the auction is still live — if our clock
            // ran ahead and we locked early, reopen.
            if (locked && !closedFinal) unlockBidding();
            setPrice(e.new_price);
            incrementCount();
            applyNewBid(e.bidder_alias, e.new_price);
        })
        .listen('.auction.extended', (e) => {
            if (e.new_end_time) {
                state.endMs = Number(e.new_end_time) * 1000;
                const el = document.querySelector('.countdown');
                if (el) el.dataset.end = new Date(state.endMs).toISOString();
            }
            // Anti-sniping pushed the end forward — reopen if we'd locked at zero.
            if (locked && !closedFinal) unlockBidding();
            toast(i18n.extended);
        })
        .listen('.auction.closed', (e) => {
            // Show the canonical result inline at once (no "live" flash), then
            // reload to pick up the server-rendered panel (winner's pay button…).
            renderClosed(e && e.winner_alias, e && e.final_price);
            toast(i18n.closed);
            setTimeout(() => window.location.reload(), 2500);
        });
})();
