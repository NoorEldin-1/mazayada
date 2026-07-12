@extends('layouts.app')

@section('title', __('nav.home'))

@section('content')
{{-- ===== Hero ===== --}}
<div class="hero" style="padding:clamp(32px,8vw,80px) 0 clamp(28px,6vw,64px)">
    <div class="container" style="text-align:center">
        <span class="hero-eyebrow" style="margin:0 auto 22px">
            <span class="pulse"></span>
            {{ __('home.live_now') }}
        </span>
        <h1 style="font-size:clamp(26px,6.5vw,52px);line-height:1.15;font-weight:700;letter-spacing:-1.5px;margin:0 0 18px">{{ __('home.hero_title_pre') }} <span class="hl">{{ __('home.hero_title_hl') }}</span><br>{{ __('home.hero_title_post') }}</h1>
        <p class="lede" style="max-width:620px;margin:0 auto 32px;text-align:center">{{ __('home.hero_desc') }}</p>

        <form action="{{ route('auctions.index') }}" method="GET" class="hero-search" style="max-width:640px;margin:0 auto">
            <div class="seg-fld">
                <span class="lbl">{{ __('home.search_keyword_label') }}</span>
                <input type="text" name="q" value="{{ request('q') }}" class="val" placeholder="{{ __('home.search_placeholder') }}">
            </div>
            <label class="seg-fld">
                <span class="lbl">{{ __('home.search_wilaya_label') }}</span>
                <select name="wilaya" class="val">
                    <option value="">{{ __('home.all_wilayas') }}</option>
                    @foreach($wilayas ?? [] as $w)
                        <option value="{{ $w->id }}" @selected(request('wilaya') == $w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="btn btn-primary" style="border-radius:11px;margin:4px">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                {{ __('common.search') }}
            </button>
        </form>

        <div class="hero-stats" style="justify-content:center;margin-top:36px">
            <div class="st"><div class="v num">58</div><div class="l">{{ __('home.stat_wilayas') }}</div></div>
            <div class="st"><div class="v num">5</div><div class="l">{{ __('home.stat_entities') }}</div></div>
            <div class="st"><div class="v num">+2400</div><div class="l">{{ __('home.stat_auctions') }}</div></div>
        </div>
    </div>
</div>

{{-- ===== Entities ===== --}}
<div class="entities-strip">
    <div class="container">
        <div class="ent-trust reveal">{{ __('home.entities_trust') }}</div>
        <div class="entities-grid">
            @foreach([
                ['code' => 'DGD', 'name' => __('home.entities.dgd'), 'count' => '+320', 'bg' => 'linear-gradient(135deg,#1B4D3E,#2D6A4F)'],
                ['code' => 'DGDPE', 'name' => __('home.entities.dgdpe'), 'count' => '+180', 'bg' => 'linear-gradient(135deg,#2E5E92,#3A86C7)'],
                ['code' => 'APC', 'name' => __('home.entities.apc'), 'count' => '+540', 'bg' => 'linear-gradient(135deg,#D4A843,#B8852E)'],
                ['code' => 'HUI', 'name' => __('home.entities.hui'), 'count' => '+95', 'bg' => 'linear-gradient(135deg,#6B45B7,#4A2B91)'],
                ['code' => 'DGI', 'name' => __('home.entities.dgi'), 'count' => '+210', 'bg' => 'linear-gradient(135deg,#B14641,#D9544E)'],
            ] as $i => $ent)
            <div class="ent-cell reveal" style="--i:{{ $i }}">
                <div class="ent-logo" style="background:{{ $ent['bg'] }}"><span>{{ $ent['code'] }}</span></div>
                <div class="ent-name">{{ $ent['name'] }}</div>
                <div class="ent-count num">{{ $ent['count'] }} {{ __('home.entity_auctions') }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ===== Features ===== --}}
<section>
    <div class="container">
        <div class="sect-head">
            <div class="sect-eyebrow">★ {{ __('home.features_eyebrow') }}</div>
            <h2>{{ __('home.features_title_pre') }} <span class="hl">{{ __('home.features_title_hl') }}</span> {{ __('home.features_title_post') }}</h2>
            <p>{{ __('home.features_subtitle') }}</p>
        </div>
        <div class="fgrid">
            @foreach([
                ['num' => '01', 'ic' => 'ic-mint', 'title' => __('home.feature_1_title'), 'desc' => __('home.feature_1_desc'), 'svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>'],
                ['num' => '02', 'ic' => 'ic-rose', 'title' => __('home.feature_2_title'), 'desc' => __('home.feature_2_desc'), 'svg' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
                ['num' => '03', 'ic' => 'ic-blue', 'title' => __('home.feature_3_title'), 'desc' => __('home.feature_3_desc'), 'svg' => '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>'],
                ['num' => '04', 'ic' => 'ic-gold', 'title' => __('home.feature_4_title'), 'desc' => __('home.feature_4_desc'), 'svg' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'],
            ] as $i => $f)
            <div class="fcard reveal" style="--i:{{ $i }}" data-spotlight>
                <div class="num-large">{{ $f['num'] }}</div>
                <div class="ic {{ $f['ic'] }}"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $f['svg'] !!}</svg></div>
                <h3>{{ $f['title'] }}</h3>
                <p>{{ $f['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===== Live Auctions ===== --}}
<section style="background:#fff;border-block:1px solid var(--line)">
    <div class="container">
        <div class="sect-head">
            <div class="sect-eyebrow">⏱ {{ __('home.live_eyebrow') }}</div>
            <h2>{{ __('home.live_title_pre') }} <span class="hl">{{ __('home.live_title_hl') }}</span> {{ __('home.live_title_post') }}</h2>
            <p>{{ __('home.live_subtitle') }}</p>
        </div>
        <div class="auc-grid">
            @forelse ($auctions as $auction)
            <a href="{{ route('auctions.show', $auction) }}" class="auc-card" style="text-decoration:none">
                <div class="auc-img">
                    @php $cover = $auction->coverPhotoUrl(); @endphp
                    @if($cover)
                    <img src="{{ $cover }}" alt="{{ $auction->title_ar }}" loading="lazy">
                    @else
                    <svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    @endif
                    @if($auction->isLive())
                    <span class="auc-tag live"><span class="dot"></span> {{ __('auctions.live') }}</span>
                    @else
                    <span class="auc-tag">{{ $auction->status->label() }}</span>
                    @endif
                </div>
                <div class="auc-body">
                    <div class="auc-cat">{{ $auction->category?->name ?? __('auctions.general_category') }}</div>
                    <div class="auc-ttl">{{ $auction->title_ar }}</div>
                    <div class="auc-loc">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        {{ $auction->wilaya?->name ?? '' }}
                    </div>
                </div>
                <div class="auc-foot">
                    <div class="pr">
                        <div class="lbl">{{ __('auctions.current_price') }}</div>
                        <div class="pv num"><x-money :centimes="$auction->currentPrice()" /></div>
                    </div>
                    <div class="bids">
                        <div class="n num">{{ $auction->bids_count }}</div>
                        {{ __('auctions.bids_word') }}
                    </div>
                </div>
            </a>
            @empty
            @for($i = 0; $i < 4; $i++)
            <div class="auc-card">
                <div class="auc-img">
                    <svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    <span class="auc-tag">{{ __('auctions.coming_soon') }}</span>
                </div>
                <div class="auc-body">
                    <div class="auc-cat">{{ __('auctions.general_category') }}</div>
                    <div class="auc-ttl">{{ __('auctions.upcoming_title') }}</div>
                    <div class="auc-loc"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg> {{ __('auctions.default_location') }}</div>
                </div>
                <div class="auc-foot">
                    <div class="pr"><div class="lbl">{{ __('auctions.starting_price') }}</div><div class="pv"><span class="money"><span class="amt">—</span> <span class="cur">{{ __('common.currency') }}</span></span></div></div>
                    <div class="bids"><div class="n num">0</div> {{ __('auctions.bids_word') }}</div>
                </div>
            </div>
            @endfor
            @endforelse
        </div>
        <div style="text-align:center;margin-top:32px">
            <a href="{{ route('auctions.index') }}" class="btn btn-outline btn-lg">{{ __('common.view_all') }}</a>
        </div>
    </div>
</section>

{{-- ===== Categories ===== --}}
<section>
    <div class="container">
        <div class="sect-head">
            <div class="sect-eyebrow">☰ {{ __('home.categories_eyebrow') }}</div>
            <h2>{{ __('home.categories_title_pre') }} <span class="hl">{{ __('home.categories_title_hl') }}</span></h2>
            <p>{{ __('home.categories_subtitle') }}</p>
        </div>
        <div class="cats">
            @php
            $catIcons = [
                'car' => '<path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18 10l-2.7-3.4A2 2 0 0 0 13.7 6H10a2 2 0 0 0-1.6.8L5.7 10 3.5 11.1c-.8.2-1.5 1-1.5 1.9v3c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
                'building' => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/>',
                'factory' => '<path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M17 18h1"/><path d="M12 18h1"/><path d="M7 18h1"/>',
                'monitor' => '<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/>',
                'recycle' => '<path d="M7 19H4.815a1.83 1.83 0 0 1-1.57-.881 1.785 1.785 0 0 1-.004-1.784L7.196 9.5"/><path d="M11 19h8.203a1.83 1.83 0 0 0 1.556-.89 1.784 1.784 0 0 0 0-1.775l-1.226-2.12"/><path d="m14 16-3 3 3 3"/><path d="M8.293 13.596 7.196 9.5 3.1 10.598"/><path d="m9.344 5.811 1.093-1.892A1.83 1.83 0 0 1 11.985 3a1.784 1.784 0 0 1 1.546.888l3.943 6.843"/><path d="m13.378 9.633 4.096 1.098 1.097-4.096"/>',
                'wheat' => '<path d="M2 22 16 8"/><path d="M3.47 12.53 5 11l1.53 1.53a3.5 3.5 0 0 1 0 4.94L5 19l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M7.47 8.53 9 7l1.53 1.53a3.5 3.5 0 0 1 0 4.94L9 15l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M11.47 4.53 13 3l1.53 1.53a3.5 3.5 0 0 1 0 4.94L13 11l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/>',
                'armchair' => '<path d="M19 9V6a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v3"/><path d="M3 16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5a2 2 0 0 0-4 0v2H7v-2a2 2 0 0 0-4 0Z"/><path d="M5 18v2"/><path d="M19 18v2"/>',
                'package' => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
            ];
            $catColors = ['ic-blue','ic-mint','ic-gold','ic-violet','ic-rose','ic-sky','ic-mint','ic-blue'];
            @endphp
            @foreach ($categories as $i => $category)
            <a href="{{ route('auctions.index', ['category_id' => $category->id]) }}" class="cat-tile reveal" style="--i:{{ $i }};text-decoration:none">
                <div class="ic {{ $catColors[$i % 8] }}">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $catIcons[$category->icon] ?? $catIcons['package'] !!}</svg>
                </div>
                <h4>{{ $category->name }}</h4>
                <div class="ct num">{{ $category->auctions_count }} {{ __('home.entity_auctions') }}</div>
                <span class="cat-go" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </span>
            </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ===== Steps ===== --}}
<section style="background:#fff;border-block:1px solid var(--line)">
    <div class="container">
        <div class="sect-head">
            <div class="sect-eyebrow">✓ {{ __('home.steps_eyebrow') }}</div>
            <h2>{{ __('home.steps_title_pre') }} <span class="hl">{{ __('home.steps_title_hl') }}</span></h2>
            <p>{{ __('home.steps_subtitle') }}</p>
        </div>
        <div class="steps">
            @foreach([
                ['n' => '1', 't' => __('home.step_1_title'), 'p' => __('home.step_1_desc')],
                ['n' => '2', 't' => __('home.step_2_title'), 'p' => __('home.step_2_desc'), 'alt' => true],
                ['n' => '3', 't' => __('home.step_3_title'), 'p' => __('home.step_3_desc')],
                ['n' => '4', 't' => __('home.step_4_title'), 'p' => __('home.step_4_desc'), 'alt' => true],
            ] as $s)
            <div class="step {{ ($s['alt'] ?? false) ? 'alt' : '' }}">
                <div class="n">{{ $s['n'] }}</div>
                <h4>{{ $s['t'] }}</h4>
                <p>{{ $s['p'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===== CTA ===== --}}
<section>
    <div class="container">
        <div class="bigcta">
            <div class="bigcta-grid">
                <div>
                    <h2>{{ __('home.cta_title') }}</h2>
                    <p>{{ __('home.cta_subtitle') }}</p>
                    <a href="{{ route('register') }}" class="btn btn-accent btn-lg">{{ __('home.cta_button') }}</a>
                </div>
                <div class="cta-bullets">
                    @foreach([
                        ['t' => __('home.cta_bullet_1_title'), 's' => __('home.cta_bullet_1_desc'), 'svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>'],
                        ['t' => __('home.cta_bullet_2_title'), 's' => __('home.cta_bullet_2_desc'), 'svg' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
                        ['t' => __('home.cta_bullet_3_title'), 's' => __('home.cta_bullet_3_desc'), 'svg' => '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>'],
                    ] as $b)
                    <div class="b">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $b['svg'] !!}</svg>
                        <div><strong>{{ $b['t'] }}</strong><span class="s">{{ $b['s'] }}</span></div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== Scroll-to-top (landing only) ===== --}}
<button type="button" class="to-top" data-to-top aria-label="{{ __('common.back_to_top') }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
</button>

@push('scripts')
<script>
(function () {
    var btn = document.querySelector('[data-to-top]');
    if (!btn) return;

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var ticking = false;

    function update() {
        var doc = document.documentElement;
        var scrolled = window.scrollY || doc.scrollTop || 0;
        var max = doc.scrollHeight - doc.clientHeight;
        var pct = max > 0 ? (scrolled / max) * 100 : 0;
        btn.style.setProperty('--p', pct.toFixed(1) + '%');
        btn.classList.toggle('is-visible', scrolled > 400);
        ticking = false;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
    }, { passive: true });
    update();

    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });
})();
</script>

{{-- Scroll-reveal: fade + rise each .reveal as it enters the viewport (once) --}}
<script>
(function () {
    var els = document.querySelectorAll('.reveal');
    if (!els.length) return;

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || !('IntersectionObserver' in window)) {
        els.forEach(function (el) { el.classList.add('is-in'); });
        return;
    }

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) {
                e.target.classList.add('is-in');
                io.unobserve(e.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

    els.forEach(function (el) { io.observe(el); });
})();
</script>

{{-- Cursor spotlight: feed pointer position into the feature cards' radial glow --}}
<script>
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var cards = document.querySelectorAll('[data-spotlight]');
    if (!cards.length) return;

    cards.forEach(function (card) {
        card.addEventListener('pointermove', function (e) {
            var r = card.getBoundingClientRect();
            card.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100).toFixed(1) + '%');
            card.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100).toFixed(1) + '%');
        });
    });
})();
</script>
@endpush
@endsection
