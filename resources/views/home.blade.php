@extends('layouts.app')

@section('title', 'الرئيسية')

@section('content')
{{-- ===== Hero ===== --}}
<div class="hero" style="padding:80px 0 64px">
    <div class="container" style="text-align:center">
        <span class="hero-eyebrow" style="margin:0 auto 22px">
            <span class="pulse"></span>
            مزايدة مباشرة الآن
        </span>
        <h1 style="font-size:52px;line-height:1.15;font-weight:700;letter-spacing:-1.5px;margin:0 0 18px">المنصة <span class="hl">الوطنية الرقمية</span><br>للمزايدات العمومية</h1>
        <p class="lede" style="max-width:620px;margin:0 auto 32px;text-align:center">منصة مزايدة تتيح لك المشاركة في المزادات العلنية التي تنظمها مختلف الهيئات الحكومية الجزائرية بكل شفافية وأمان.</p>

        <form action="{{ route('auctions.index') }}" method="GET" class="hero-search" style="max-width:640px;margin:0 auto">
            <div class="seg-fld">
                <span class="lbl">الكلمة المفتاحية</span>
                <input type="text" name="search" class="val" placeholder="ابحث عن مزايدة...">
            </div>
            <div class="seg-fld">
                <span class="lbl">الولاية</span>
                <span class="val" style="color:var(--muted)">كل الولايات</span>
            </div>
            <button type="submit" class="btn btn-primary" style="border-radius:11px;margin:4px">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                بحث
            </button>
        </form>

        <div class="hero-stats" style="justify-content:center;margin-top:36px">
            <div class="st"><div class="v num">58</div><div class="l">ولاية</div></div>
            <div class="st"><div class="v num">5</div><div class="l">جهات حكومية</div></div>
            <div class="st"><div class="v num">+2400</div><div class="l">مزايدة</div></div>
        </div>
    </div>
</div>

{{-- ===== Entities ===== --}}
<div class="entities-strip">
    <div class="container">
        <div class="entities-grid">
            @foreach([
                ['code' => 'DGD', 'name' => 'المديرية العامة للجمارك', 'count' => '+320', 'bg' => 'linear-gradient(135deg,#1B4D3E,#2D6A4F)'],
                ['code' => 'DGDPE', 'name' => 'أملاك الدولة', 'count' => '+180', 'bg' => 'linear-gradient(135deg,#2E5E92,#3A86C7)'],
                ['code' => 'APC', 'name' => 'المجالس البلدية', 'count' => '+540', 'bg' => 'linear-gradient(135deg,#D4A843,#B8852E)'],
                ['code' => 'HUI', 'name' => 'المحضرون القضائيون', 'count' => '+95', 'bg' => 'linear-gradient(135deg,#6B45B7,#4A2B91)'],
                ['code' => 'DGI', 'name' => 'المديرية العامة للضرائب', 'count' => '+210', 'bg' => 'linear-gradient(135deg,#B14641,#D9544E)'],
            ] as $ent)
            <div class="ent-cell">
                <div class="ent-logo" style="background:{{ $ent['bg'] }}">{{ $ent['code'] }}</div>
                <div class="ent-name">{{ $ent['name'] }}</div>
                <div class="ent-count num">{{ $ent['count'] }} مزايدة</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ===== Features ===== --}}
<section>
    <div class="container">
        <div class="sect-head">
            <div class="sect-eyebrow">★ لماذا مزايدة؟</div>
            <h2>منصة <span class="hl">موثوقة</span> بمعايير عالمية</h2>
            <p>نوفر لك بيئة رقمية آمنة وشفافة للمشاركة في المزايدات العمومية بكل يسر.</p>
        </div>
        <div class="fgrid">
            @foreach([
                ['num' => '01', 'ic' => 'ic-mint', 'title' => 'شفافية كاملة', 'desc' => 'جميع العمليات مسجلة ومراقبة لضمان نزاهة المزايدات وحماية حقوق المشاركين.', 'svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>'],
                ['num' => '02', 'ic' => 'ic-rose', 'title' => 'مزايدة حية', 'desc' => 'شارك في المزايدات مباشرة عبر الإنترنت مع تحديث فوري للأسعار والعروض.', 'svg' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
                ['num' => '03', 'ic' => 'ic-blue', 'title' => 'إطار قانوني', 'desc' => 'المنصة تعمل وفق القوانين الجزائرية المنظمة للمزايدات العمومية والصفقات.', 'svg' => '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>'],
                ['num' => '04', 'ic' => 'ic-gold', 'title' => 'ثلاثي اللغات', 'desc' => 'واجهة بالعربية والفرنسية والإنجليزية لخدمة جميع المواطنين.', 'svg' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'],
            ] as $f)
            <div class="fcard">
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
            <div class="sect-eyebrow">⏱ مزايدات نشطة</div>
            <h2>أحدث <span class="hl">المزايدات</span> المتاحة</h2>
            <p>تصفّح المزايدات الجارية وشارك فيها مباشرة من منزلك.</p>
        </div>
        <div class="auc-grid">
            @forelse ($auctions as $auction)
            <a href="{{ route('auctions.show', $auction) }}" class="auc-card" style="text-decoration:none">
                <div class="auc-img">
                    <svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    @if($auction->isLive())
                    <span class="auc-tag live"><span class="dot"></span> مباشر</span>
                    @else
                    <span class="auc-tag">{{ $auction->status->label() }}</span>
                    @endif
                </div>
                <div class="auc-body">
                    <div class="auc-cat">{{ $auction->category?->name_ar ?? 'عام' }}</div>
                    <div class="auc-ttl">{{ $auction->title_ar }}</div>
                    <div class="auc-loc">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        {{ $auction->wilaya?->name_ar ?? '' }}
                    </div>
                </div>
                <div class="auc-foot">
                    <div class="pr">
                        <div class="lbl">السعر الحالي</div>
                        <div class="pv num">{{ dzd($auction->currentPrice()) }}</div>
                    </div>
                    <div class="bids">
                        <div class="n num">{{ $auction->bids_count }}</div>
                        عرض
                    </div>
                </div>
            </a>
            @empty
            @for($i = 0; $i < 4; $i++)
            <div class="auc-card">
                <div class="auc-img">
                    <svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    <span class="auc-tag">قريباً</span>
                </div>
                <div class="auc-body">
                    <div class="auc-cat">عام</div>
                    <div class="auc-ttl">مزايدة قادمة قريباً</div>
                    <div class="auc-loc"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg> الجزائر</div>
                </div>
                <div class="auc-foot">
                    <div class="pr"><div class="lbl">السعر الابتدائي</div><div class="pv num">— دج</div></div>
                    <div class="bids"><div class="n num">0</div> عرض</div>
                </div>
            </div>
            @endfor
            @endforelse
        </div>
        <div style="text-align:center;margin-top:32px">
            <a href="{{ route('auctions.index') }}" class="btn btn-outline btn-lg">عرض الكل ←</a>
        </div>
    </div>
</section>

{{-- ===== Categories ===== --}}
<section>
    <div class="container">
        <div class="sect-head">
            <div class="sect-eyebrow">☰ الفئات</div>
            <h2>تصفّح حسب <span class="hl">الفئة</span></h2>
            <p>اختر الفئة التي تهمك للوصول بسرعة إلى المزايدات المناسبة.</p>
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
            <a href="{{ route('auctions.index', ['category_id' => $category->id]) }}" class="cat-tile" style="text-decoration:none">
                <div class="ic {{ $catColors[$i % 8] }}">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $catIcons[$category->icon] ?? $catIcons['package'] !!}</svg>
                </div>
                <h4>{{ $category->name_ar }}</h4>
                <div class="ct num">{{ $category->auctions_count }} مزايدة</div>
            </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ===== Steps ===== --}}
<section style="background:#fff;border-block:1px solid var(--line)">
    <div class="container">
        <div class="sect-head">
            <div class="sect-eyebrow">✓ كيف تعمل المنصة؟</div>
            <h2>أربع خطوات <span class="hl">بسيطة</span></h2>
            <p>اتبع هذه الخطوات للمشاركة في المزايدات العمومية عبر المنصة.</p>
        </div>
        <div class="steps">
            @foreach([
                ['n' => '1', 't' => 'سجّل حسابك', 'p' => 'أنشئ حسابك برقم التعريف الوطني وأكمل عملية التحقق من الهوية.'],
                ['n' => '2', 't' => 'تصفّح المزايدات', 'p' => 'استعرض المزايدات المتاحة حسب الفئة والولاية واختر ما يناسبك.', 'alt' => true],
                ['n' => '3', 't' => 'قدّم عرضك', 'p' => 'سجّل في المزايدة وقدّم عرضك مباشرة مع متابعة فورية للأسعار.'],
                ['n' => '4', 't' => 'ادفع واستلم', 'p' => 'في حال الفوز، أتمم عملية الدفع واستلم الوثائق الرسمية.', 'alt' => true],
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
                    <h2>انضم إلى آلاف المواطنين على منصة مزايدة</h2>
                    <p>سجّل الآن واستفد من الفرص المتاحة في المزايدات العمومية عبر كامل التراب الوطني.</p>
                    <a href="{{ route('register') }}" class="btn btn-accent btn-lg">أنشئ حسابك مجاناً ←</a>
                </div>
                <div class="cta-bullets">
                    @foreach([
                        ['t' => 'أمان تام', 's' => 'تشفير كامل وحماية بيانات متقدمة', 'svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>'],
                        ['t' => 'مزايدة في الوقت الحقيقي', 's' => 'تحديثات فورية لجميع العروض', 'svg' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
                        ['t' => 'إطار قانوني', 's' => 'وفق التشريعات الجزائرية المعمول بها', 'svg' => '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>'],
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
@endsection
