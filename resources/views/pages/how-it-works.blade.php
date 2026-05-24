@extends('layouts.app')
@section('title', 'كيف يعمل')
@section('content')

<section style="padding:48px 0 72px">
    <div class="container">
        <div class="sect-head">
            <div class="sect-eyebrow">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                دليل الاستخدام
            </div>
            <h2>كيف <span class="hl">يعمل</span> النظام؟</h2>
            <p>ست خطوات بسيطة للمشاركة في المزايدات العمومية عبر منصة مزايدة</p>
        </div>

        <div class="steps" style="grid-template-columns:repeat(3,1fr);gap:32px;margin-bottom:64px">
            @foreach([
                ['n' => '1', 'title' => 'أنشئ حسابك', 'desc' => 'سجّل برقم التعريف الوطني (NIN) وأكّد هويتك عبر رمز OTP يُرسل لهاتفك وبريدك الإلكتروني.'],
                ['n' => '2', 'title' => 'أكمل التحقق (KYC)', 'desc' => 'ارفع صور بطاقة الهوية والسيلفي، ثم أكمل معلوماتك الشخصية. يُراجع طلبك خلال 48 ساعة.', 'alt' => true],
                ['n' => '3', 'title' => 'تصفّح المزايدات', 'desc' => 'استعرض المزايدات المتاحة حسب الفئة والولاية والجهة الحكومية. اطلع على التفاصيل والصور وكراسة الشروط.'],
                ['n' => '4', 'title' => 'سجّل وادفع الكفالة', 'desc' => 'اختر المزايدة التي تريد المشاركة فيها، ادفع مبلغ الكفالة ورسوم الدخول للتأهل.', 'alt' => true],
                ['n' => '5', 'title' => 'قدّم عرضك', 'desc' => 'شارك في المزايدة الحية. قدّم عروضك عبر الأزرار السريعة أو بإدخال مبلغ مخصص. كل عرض مسجّل بالتوقيت وعنوان IP.'],
                ['n' => '6', 'title' => 'ادفع واستلم', 'desc' => 'إذا فزت بالمزايدة، أكمل الدفع خلال المهلة القانونية (8 أيام للمنقولات، 15 يوم للعقارات) واستلم وثيقة الترسية.', 'alt' => true],
            ] as $step)
            <div class="step {{ ($step['alt'] ?? false) ? 'alt' : '' }}" style="position:relative">
                <div class="n">{{ $step['n'] }}</div>
                <h4>{{ $step['title'] }}</h4>
                <p>{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- FAQ Section --}}
        <div class="sect-head">
            <h2>أسئلة <span class="hl">شائعة</span></h2>
        </div>
        <div style="max-width:800px;margin:0 auto;display:grid;gap:14px">
            @foreach([
                ['q' => 'هل المنصة مجانية؟', 'a' => 'التسجيل مجاني. تُدفع رسوم الكفالة والدخول وكراسة الشروط فقط عند المشاركة في مزايدة محددة.'],
                ['q' => 'ما هي المدة اللازمة للتحقق من الهوية؟', 'a' => 'يتم مراجعة طلبات التحقق خلال 48 ساعة عمل كحد أقصى.'],
                ['q' => 'هل يمكنني المشاركة من خارج الجزائر؟', 'a' => 'نعم، يمكن لأي مواطن جزائري المشاركة عبر الإنترنت من أي مكان بشرط إتمام التحقق من الهوية.'],
                ['q' => 'ماذا يحدث إذا قدّم شخص عرضاً في آخر 30 ثانية؟', 'a' => 'تُمدد المزايدة تلقائياً 5 دقائق إضافية لضمان العدالة بين المشاركين.'],
                ['q' => 'كيف أسترجع مبلغ الكفالة؟', 'a' => 'تُعاد الكفالة تلقائياً لجميع المشاركين غير الفائزين بعد إغلاق المزايدة.'],
            ] as $faq)
            <div class="card card-pad" style="cursor:pointer">
                <strong style="font-size:15px;color:var(--ink)">{{ $faq['q'] }}</strong>
                <p style="font-size:13px;color:var(--muted);margin:8px 0 0;line-height:1.7">{{ $faq['a'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
