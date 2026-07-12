// ============================================================================
//  Mazayada — Auction media gallery (public auction detail page)
// ----------------------------------------------------------------------------
//  A modern, RTL-aware media gallery built on Swiper. Loaded ONLY from
//  auctions/show.blade.php (its own Vite entry), decoupled from the realtime
//  bidding bundle so it runs on every auction page.
//
//  Structure (rendered by Blade under [data-gallery]):
//    • [data-hero]   → the main stage Swiper (photos + one short video)
//    • [data-thumbs] → a free-scrolling thumbnail strip, linked to the hero
//    • a fullscreen lightbox (built here, on first open) with pinch /
//      double-tap / wheel zoom for inspecting the asset up close.
//
//  Everything degrades gracefully: a missing root, a single slide, or a
//  missing thumb strip each just skips the part that needs it.
// ============================================================================
import Swiper from 'swiper';
import { Navigation, Thumbs, Zoom, Keyboard, A11y, FreeMode, Mousewheel } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/thumbs';
import 'swiper/css/zoom';
import 'swiper/css/free-mode';

(function () {
    const root = document.querySelector('[data-gallery]');
    if (!root) return;

    const heroEl = root.querySelector('[data-hero]');
    if (!heroEl) return;

    const thumbsEl = root.querySelector('[data-thumbs]');
    const countEl = root.querySelector('[data-gcount]');
    const prevEl = root.querySelector('[data-prev]');
    const nextEl = root.querySelector('[data-next]');
    const expandEl = root.querySelector('[data-expand]');

    // Localized strings (Arabic primary; FR/EN supplied by Blade data-attrs).
    const a11y = {
        prev: root.dataset.a11yPrev || '',
        next: root.dataset.a11yNext || '',
        close: root.dataset.a11yClose || '',
        zoom: root.dataset.a11yZoom || '',
    };

    // Unified media list, read from the rendered hero slides — the single
    // source of truth, so the lightbox never drifts from the page.
    const media = Array.from(heroEl.querySelectorAll('.swiper-slide')).map((s) => {
        const v = s.querySelector('video');
        if (v) return { type: 'video', src: v.currentSrc || v.getAttribute('src') || '' };
        const img = s.querySelector('img');
        return { type: 'image', src: (img && img.getAttribute('src')) || '' };
    });
    const total = media.length;
    const hasMany = total > 1;

    // ── Inline SVG icons (match the rest of the page) ──────────────────────
    const ICON = {
        prev: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>',
        next: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>',
        close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>',
    };

    // ── Helpers ────────────────────────────────────────────────────────────
    function pauseVideosExcept(scope, activeSlide) {
        scope.querySelectorAll('video').forEach((v) => {
            if (!activeSlide || !activeSlide.contains(v)) { try { v.pause(); } catch (_) { /* noop */ } }
        });
    }
    function pauseAll(scope) {
        scope.querySelectorAll('video').forEach((v) => { try { v.pause(); } catch (_) { /* noop */ } });
    }
    function escAttr(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    // ── Thumbnail strip (free-scrolling, linked to the hero) ───────────────
    const thumbsSwiper = (thumbsEl && hasMany)
        ? new Swiper(thumbsEl, {
            modules: [FreeMode, Mousewheel],
            slidesPerView: 'auto',
            spaceBetween: 8,
            freeMode: true,
            watchSlidesProgress: true,
            mousewheel: { forceToAxis: true },
        })
        : null;

    // ── Hero stage ─────────────────────────────────────────────────────────
    const heroSwiper = new Swiper(heroEl, {
        modules: [Navigation, Keyboard, A11y].concat(thumbsSwiper ? [Thumbs] : []),
        speed: 420,
        grabCursor: false, // image slides use a zoom-in cursor (see CSS)
        spaceBetween: 0,
        threshold: 6,
        navigation: hasMany && prevEl && nextEl ? { prevEl, nextEl } : false,
        keyboard: { enabled: true },
        thumbs: thumbsSwiper ? { swiper: thumbsSwiper } : undefined,
        a11y: { prevSlideMessage: a11y.prev, nextSlideMessage: a11y.next },
        on: {
            slideChange() {
                if (countEl) countEl.textContent = (this.activeIndex + 1) + ' / ' + total;
                pauseVideosExcept(heroEl, this.slides[this.activeIndex]);
            },
            // A genuine click on an image (not the tail of a drag) opens the
            // zoom lightbox. Video slides keep their native controls.
            click() {
                if (!this.allowClick) return;
                const slide = this.slides[this.activeIndex];
                if (slide && slide.getAttribute('data-type') === 'image') openLightbox(this.activeIndex);
            },
        },
    });
    if (countEl) countEl.textContent = '1 / ' + total;

    // ── Fullscreen zoom lightbox (built lazily on first open) ──────────────
    let lb = null;
    let lbSwiper = null;
    let lastFocus = null;

    function lbSlideHtml(m) {
        if (m.type === 'video') {
            return '<div class="swiper-slide mzd-lb-slide" data-type="video">'
                + '<video controls playsinline preload="metadata" src="' + escAttr(m.src) + '"></video></div>';
        }
        return '<div class="swiper-slide mzd-lb-slide" data-type="image">'
            + '<div class="swiper-zoom-container"><img src="' + escAttr(m.src) + '" alt=""></div></div>';
    }

    function updateLbCount() {
        const c = lb && lb.querySelector('.mzd-lb-count');
        if (c && lbSwiper) c.textContent = (lbSwiper.activeIndex + 1) + ' / ' + total;
    }

    function buildLightbox() {
        lb = document.createElement('div');
        lb.className = 'mzd-lightbox';
        lb.setAttribute('role', 'dialog');
        lb.setAttribute('aria-modal', 'true');
        lb.hidden = true;
        if (a11y.zoom) lb.setAttribute('aria-label', a11y.zoom);

        lb.innerHTML =
            '<div class="mzd-lb-backdrop" data-lb-close></div>'
            + '<div class="mzd-lb-stage">'
            + '<div class="swiper mzd-lb-swiper"><div class="swiper-wrapper">'
            + media.map(lbSlideHtml).join('')
            + '</div>'
            + '</div>'
            + '</div>'
            + '<button type="button" class="mzd-lb-close" data-lb-close aria-label="' + escAttr(a11y.close) + '">' + ICON.close + '</button>'
            + (hasMany
                ? '<button type="button" class="mzd-nav mzd-lb-nav mzd-lb-prev" aria-label="' + escAttr(a11y.prev) + '">' + ICON.prev + '</button>'
                + '<button type="button" class="mzd-nav mzd-lb-nav mzd-lb-next" aria-label="' + escAttr(a11y.next) + '">' + ICON.next + '</button>'
                + '<span class="mzd-lb-count num"></span>'
                : '');

        document.body.appendChild(lb);

        lbSwiper = new Swiper(lb.querySelector('.mzd-lb-swiper'), {
            modules: [Zoom, Navigation, Keyboard, A11y],
            spaceBetween: 24,
            speed: 360,
            zoom: { maxRatio: 4, toggle: true },
            keyboard: { enabled: true },
            navigation: hasMany ? { prevEl: lb.querySelector('.mzd-lb-prev'), nextEl: lb.querySelector('.mzd-lb-next') } : false,
            a11y: { prevSlideMessage: a11y.prev, nextSlideMessage: a11y.next },
            on: {
                slideChange() {
                    // Reset any zoom carried over, keep the page in sync, stop audio.
                    if (this.zoom) this.zoom.out();
                    heroSwiper.slideTo(this.activeIndex, 0);
                    updateLbCount();
                    pauseVideosExcept(lb, this.slides[this.activeIndex]);
                },
            },
        });

        lb.querySelectorAll('[data-lb-close]').forEach((el) => el.addEventListener('click', closeLightbox));
        document.addEventListener('keydown', (e) => {
            if (!lb || lb.hidden) return;
            if (e.key === 'Escape') closeLightbox();
        });
    }

    function openLightbox(index) {
        if (!lb) buildLightbox();
        lastFocus = document.activeElement;
        pauseAll(heroEl); // hand audio over to the lightbox
        lb.hidden = false;
        document.documentElement.classList.add('mzd-lb-open');
        // The swiper was initialised while hidden (display:none → zero-width);
        // recompute geometry now it's visible, then jump to the tapped slide.
        lbSwiper.update();
        if (lbSwiper.zoom) lbSwiper.zoom.out();
        lbSwiper.slideTo(index, 0);
        updateLbCount();
        pauseVideosExcept(lb, lbSwiper.slides[lbSwiper.activeIndex]);
        const closeBtn = lb.querySelector('.mzd-lb-close');
        if (closeBtn) closeBtn.focus();
    }

    function closeLightbox() {
        if (!lb || lb.hidden) return;
        if (lbSwiper && lbSwiper.zoom) lbSwiper.zoom.out();
        pauseAll(lb);
        lb.hidden = true;
        document.documentElement.classList.remove('mzd-lb-open');
        if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
    }

    // The expand button opens the lightbox at the current slide (works for
    // images and video alike).
    if (expandEl) {
        expandEl.addEventListener('click', () => openLightbox(heroSwiper.activeIndex));
    }
})();
