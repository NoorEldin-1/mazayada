@extends('layouts.app')
@section('title', 'حول المنصة')
@section('content')

<section style="padding:48px 0 72px">
    <div class="container">
        <div class="sect-head">
            <div class="sect-eyebrow">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                من نحن
            </div>
            <h2>حول <span class="hl">منصة مزايدة</span></h2>
            <p>المنصة الوطنية الرقمية للمزايدات والإيجارات العمومية في الجزائر</p>
        </div>

        {{-- Mission --}}
        <div class="card card-pad" style="margin-bottom:32px;max-width:900px;margin-inline:auto">
            <h3 style="font-size:20px;font-weight:700;margin:0 0 12px;color:var(--primary)">مهمّتنا</h3>
            <p style="font-size:15px;color:var(--ink-2);line-height:1.8;margin:0">
                تهدف منصة مزايدة إلى رقمنة دورة حياة المزايدة العمومية بالكامل، من الإعلان والنشر إلى المزايدة الحية والترسية والدفع.
                نضمن الشفافية الكاملة والنزاهة في كل عملية، مع الالتزام بالإطار القانوني الجزائري.
            </p>
        </div>

        {{-- 4 Pillars --}}
        <div class="fgrid" style="margin-bottom:48px">
            @foreach([
                ['title' => 'شفافية', 'desc' => 'كل مزايدة مسجلة رقمياً بالتوقيت وعنوان IP والمتصفح. لا مجال للتلاعب.', 'color' => '#2D6A4F'],
                ['title' => 'سرعة', 'desc' => 'تحديثات فورية عبر WebSocket. مزايدة حية بتمديد تلقائي لضمان العدالة.', 'color' => '#3A86C7'],
                ['title' => 'أمان', 'desc' => 'تحقق إلزامي من الهوية (KYC). تشفير البيانات وحماية الحسابات.', 'color' => '#D4A843'],
                ['title' => 'عدالة', 'desc' => 'هوية المزايد مجهولة. لا تمييز. الفائز هو صاحب أعلى عرض.', 'color' => '#6B45B7'],
            ] as $pillar)
            <div class="fcard">
                <div class="ic" style="background:{{ $pillar['color'] }}15;color:{{ $pillar['color'] }}">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3>{{ $pillar['title'] }}</h3>
                <p>{{ $pillar['desc'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- Government Entities --}}
        <div class="sect-head"><h2>الجهات <span class="hl">الحكومية</span></h2></div>
        <div class="entities-grid" style="margin-bottom:48px">
            @foreach([
                ['code' => 'DGD', 'name' => 'المديرية العامة للجمارك', 'desc' => 'بيع البضائع المحجوزة والمصادرة', 'color' => '#2D6A4F'],
                ['code' => 'DGDPE', 'name' => 'أملاك الدولة', 'desc' => 'تأجير وبيع الممتلكات العمومية', 'color' => '#3A86C7'],
                ['code' => 'APC', 'name' => 'المجالس البلدية', 'desc' => 'مزادات البلديات', 'color' => '#9A7008'],
                ['code' => 'HUI', 'name' => 'المحضرون القضائيون', 'desc' => 'البيع بالمزاد العلني بأمر قضائي', 'color' => '#6B45B7'],
                ['code' => 'DGI', 'name' => 'المديرية العامة للضرائب', 'desc' => 'بيع الأصول الضريبية', 'color' => '#B14641'],
            ] as $entity)
            <div class="ent-cell">
                <div class="ent-logo" style="background:{{ $entity['color'] }}">{{ $entity['code'] }}</div>
                <div class="ent-name">{{ $entity['name'] }}</div>
                <div class="ent-count" style="font-size:11px;color:var(--muted)">{{ $entity['desc'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Legal Framework --}}
        <div class="sect-head"><h2>الإطار <span class="hl">القانوني</span></h2></div>
        <div style="max-width:800px;margin:0 auto;display:grid;gap:12px">
            @foreach([
                'المرسوم التنفيذي 97-33 — رسوم المحضر القضائي',
                'المرسوم التنفيذي 10-210 — رقم التعريف الوطني',
                'قانون الإجراءات المدنية والإدارية — البيع بالمزاد العلني',
                'قانون الجمارك — بيع البضائع المحجوزة',
                'قانون أملاك الدولة — تأجير وبيع الممتلكات العمومية',
            ] as $law)
            <div class="card card-pad" style="display:flex;align-items:center;gap:12px">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(27,77,62,.08);color:var(--primary);display:grid;place-items:center;flex-shrink:0">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <span style="font-size:14px;font-weight:500">{{ $law }}</span>
            </div>
            @endforeach
        </div>

        {{-- Contact --}}
        <div style="text-align:center;margin-top:48px">
            <p style="color:var(--muted);font-size:14px;margin:0 0 8px">للتواصل معنا</p>
            <p style="font-size:16px;margin:0"><strong>contact@mazayada.dz</strong> · <span class="num">+213 (0) 23 45 67 89</span></p>
        </div>
    </div>
</section>

@endsection
