{{--
    Auth marketing carousel.

    Swipeable, auto-advancing slides shown in the auth layout's green side panel
    (replaces the old static testimonial card). Teaches platform rules while the
    user signs up / logs in. All copy comes from lang/{ar,fr,en}/auth.php under
    the `carousel.*` keys. Direction-aware (RTL/LTR) and desktop-only — the whole
    .auth-left panel is hidden ≤980px by mazayada.css.

    Usage:  <x-auth-carousel />
--}}
@php
    // Slide order + per-slide body shape. Titles/icons resolve from the key.
    $slides = [
        ['key' => 'input', 'lines' => [
            __('auth.carousel.input_nin'),
            __('auth.carousel.input_phone'),
            __('auth.carousel.input_age'),
        ]],
        ['key' => 'kyc', 'body' => __('auth.carousel.kyc_body')],
        ['key' => 'security', 'body' => __('auth.carousel.security_body')],
        ['key' => 'auction', 'body' => __('auth.carousel.auction_body')],
    ];
@endphp

<div class="auth-carousel" role="region" aria-roledescription="carousel" aria-label="{{ __('auth.carousel.aria_label') }}" data-carousel>
    <button type="button" class="auth-carousel-arrow auth-carousel-prev" data-prev aria-label="{{ __('auth.carousel.prev') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </button>

    <div class="auth-carousel-viewport" data-viewport>
        <div class="auth-carousel-track" data-track>
            @foreach($slides as $i => $slide)
                <div class="auth-carousel-slide" role="group" aria-roledescription="slide" aria-label="{{ __('auth.carousel.go_to', ['num' => $i + 1]) }}">
                    <span class="auth-carousel-icon" aria-hidden="true">
                        @switch($slide['key'])
                            @case('input')
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                                @break
                            @case('kyc')
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
                                @break
                            @case('security')
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                                @break
                            @case('auction')
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                                @break
                        @endswitch
                    </span>
                    <h3 class="auth-carousel-title">{{ __("auth.carousel.{$slide['key']}_title") }}</h3>
                    @if(isset($slide['lines']))
                        <ul class="auth-carousel-list">
                            @foreach($slide['lines'] as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="auth-carousel-body">{{ $slide['body'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <button type="button" class="auth-carousel-arrow auth-carousel-next" data-next aria-label="{{ __('auth.carousel.next') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
    </button>

    <div class="auth-carousel-dots">
        @foreach($slides as $i => $slide)
            <button type="button" class="auth-carousel-dot {{ $i === 0 ? 'on' : '' }}" data-dot="{{ $i }}" aria-label="{{ __('auth.carousel.go_to', ['num' => $i + 1]) }}" @if($i === 0) aria-current="true" @endif></button>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
(function () {
    function initCarousel(root) {
        var track = root.querySelector('[data-track]');
        var viewport = root.querySelector('[data-viewport]');
        var slides = track ? track.children : [];
        var dots = root.querySelectorAll('[data-dot]');
        var prevBtn = root.querySelector('[data-prev]');
        var nextBtn = root.querySelector('[data-next]');
        var count = slides.length;
        if (!track || count <= 1) return;

        var index = 0;
        var timer = null;
        var INTERVAL = 6000;
        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function isRtl() {
            return getComputedStyle(viewport).direction === 'rtl';
        }

        function render() {
            var pct = index * 100;
            // RTL lays slides out right-to-left, so the sign of the shift flips.
            track.style.transform = 'translateX(' + (isRtl() ? pct : -pct) + '%)';
            for (var i = 0; i < dots.length; i++) {
                var on = i === index;
                dots[i].classList.toggle('on', on);
                if (on) { dots[i].setAttribute('aria-current', 'true'); }
                else { dots[i].removeAttribute('aria-current'); }
            }
        }

        function go(i) { index = (i % count + count) % count; render(); }
        function next() { go(index + 1); }
        function prev() { go(index - 1); }

        function start() {
            if (reduceMotion || timer) return;
            timer = setInterval(next, INTERVAL);
        }
        function stop() {
            if (timer) { clearInterval(timer); timer = null; }
        }

        if (nextBtn) nextBtn.addEventListener('click', next);
        if (prevBtn) prevBtn.addEventListener('click', prev);
        for (var d = 0; d < dots.length; d++) {
            (function (btn) {
                btn.addEventListener('click', function () {
                    go(parseInt(btn.getAttribute('data-dot'), 10) || 0);
                });
            })(dots[d]);
        }

        // Pause auto-advance while the user is reading / interacting.
        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);
        root.addEventListener('focusin', stop);
        root.addEventListener('focusout', start);

        // Keyboard — arrow keys mapped by reading direction.
        root.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') { isRtl() ? prev() : next(); }
            else if (e.key === 'ArrowLeft') { isRtl() ? next() : prev(); }
        });

        // Touch / pointer swipe.
        var startX = null;
        viewport.addEventListener('pointerdown', function (e) { startX = e.clientX; stop(); });
        viewport.addEventListener('pointerup', function (e) {
            if (startX === null) return;
            var dx = e.clientX - startX;
            startX = null;
            if (Math.abs(dx) > 40) {
                var forward = dx < 0;          // swipe toward inline-start = next (LTR)
                if (isRtl()) forward = !forward;
                forward ? next() : prev();
            }
            start();
        });
        viewport.addEventListener('pointercancel', function () { startX = null; start(); });

        render();
        start();
    }

    function boot() {
        var nodes = document.querySelectorAll('[data-carousel]');
        for (var i = 0; i < nodes.length; i++) { initCarousel(nodes[i]); }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
@endpush
