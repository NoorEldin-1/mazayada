@extends('layouts.citizen')
@section('title', 'التحقق من الهوية')
@section('content')

<div style="margin-bottom:24px">
    <h2 style="font-size:24px;font-weight:700;margin:0 0 8px">التحقق من الهوية (KYC)</h2>
    <p style="color:var(--muted);font-size:14px;margin:0">أكمل التحقق من هويتك للمشاركة في المزايدات</p>
</div>

{{-- Step indicator --}}
<div class="steps-h">
    <div class="si {{ auth()->user()->biometrics && auth()->user()->biometrics->id_front_path ? 'done' : 'cur' }}">
        <div class="n">1</div>
        <div class="tx"><div class="l">الخطوة الأولى</div><div class="t">الوثائق البيومترية</div></div>
    </div>
    <div class="ln {{ auth()->user()->biometrics && auth()->user()->biometrics->id_front_path ? 'done' : '' }}"></div>
    <div class="si {{ auth()->user()->kyc_status->value === 'COMPLETE' ? 'done' : (auth()->user()->biometrics && auth()->user()->biometrics->id_front_path ? 'cur' : '') }}">
        <div class="n">2</div>
        <div class="tx"><div class="l">الخطوة الثانية</div><div class="t">المعلومات الشخصية</div></div>
    </div>
    <div class="ln"></div>
    <div class="si">
        <div class="n">3</div>
        <div class="tx"><div class="l">الخطوة الثالثة</div><div class="t">الإرسال والمراجعة</div></div>
    </div>
</div>

@if($errors->any())
<div style="background:#FBE2E0;color:#8E2F2A;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:8px">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    {{ $errors->first() }}
</div>
@endif

@if(session('success'))
<div style="background:#E5F3EC;color:#1d6045;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:13px">
    {{ session('success') }}
</div>
@endif

{{-- Step 1: Document Upload --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-h"><h3>الوثائق البيومترية</h3></div>
    <div class="card-pad">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
            @foreach(['id-front' => 'واجهة بطاقة الهوية', 'id-back' => 'خلفية بطاقة الهوية', 'selfie-with-id' => 'سيلفي مع بطاقة الهوية'] as $type => $label)
            <div>
                <form action="{{ route('citizen.kyc.upload', $type) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @php
                        $bio = auth()->user()->biometrics;
                        $field = str_replace('-', '_', $type) . '_path';
                        $uploaded = $bio && $bio->$field;
                    @endphp
                    <div class="upbox {{ $uploaded ? 'done' : '' }}" onclick="this.querySelector('input[type=file]')?.click()">
                        <div class="ic">
                            @if($uploaded)
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                            @else
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                            @endif
                        </div>
                        <div class="t">{{ $label }}</div>
                        <div class="s">{{ $uploaded ? 'تم الرفع ✓' : 'JPG أو PNG — حد أقصى 5 ميغابايت' }}</div>
                        @if(!$uploaded)
                            <input type="file" name="file" accept="image/jpeg,image/png" style="display:none" onchange="this.form.submit()">
                        @endif
                    </div>
                </form>
            </div>
            @endforeach
        </div>
        <div style="background:#FBEFD6;border-radius:14px;padding:18px;margin-top:18px;font-size:13px;color:#8A6310">
            <strong>متطلبات الصور:</strong>
            <ul style="margin:8px 0 0;padding-inline-start:18px;line-height:2">
                <li>صورة واضحة بدون انعكاسات</li>
                <li>جميع الزوايا والنصوص مقروءة</li>
                <li>الحجم الأقصى: 5 ميغابايت لكل ملف</li>
                <li>الصيغ المقبولة: JPG، PNG</li>
            </ul>
        </div>
    </div>
</div>

{{-- Step 2: Personal Info --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-h"><h3>المعلومات الشخصية</h3></div>
    <div class="card-pad">
        <form action="{{ route('citizen.kyc.submit') }}" method="POST">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="field">
                    <label>الاسم بالفرنسية <span class="req">*</span></label>
                    <input class="input" name="first_name_fr" value="{{ old('first_name_fr', auth()->user()->first_name_fr) }}" dir="ltr" required>
                </div>
                <div class="field">
                    <label>اللقب بالفرنسية <span class="req">*</span></label>
                    <input class="input" name="last_name_fr" value="{{ old('last_name_fr', auth()->user()->last_name_fr) }}" dir="ltr" required>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="field">
                    <label>اسم الأب</label>
                    <input class="input" name="father_name" value="{{ old('father_name', auth()->user()->father_name) }}">
                </div>
                <div class="field">
                    <label>اسم الأم الكامل</label>
                    <input class="input" name="mother_fullname" value="{{ old('mother_fullname', auth()->user()->mother_fullname) }}">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="field">
                    <label>الولاية <span class="req">*</span></label>
                    <select class="input" name="wilaya_id" id="wilaya-select" required>
                        <option value="">— اختر الولاية —</option>
                        @foreach(\App\Models\Wilaya::orderBy('id')->get() as $w)
                            <option value="{{ $w->id }}">{{ $w->id }} — {{ $w->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>البلدية</label>
                    <select class="input" name="commune_id" id="commune-select">
                        <option value="">— اختر الولاية أولاً —</option>
                    </select>
                </div>
            </div>
            <div class="field" style="margin-bottom:16px">
                <label>العنوان الكامل</label>
                <input class="input" name="address" value="{{ old('address', auth()->user()->address) }}">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="field">
                    <label>الرمز البريدي</label>
                    <input class="input" name="postal_code" value="{{ old('postal_code', auth()->user()->postal_code) }}" dir="ltr" maxlength="5" pattern="\d{5}">
                </div>
                <div class="field">
                    <label>المهنة</label>
                    <input class="input" name="profession" value="{{ old('profession', auth()->user()->profession) }}">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
                <div class="field">
                    <label>الدخل السنوي المتوقع (دج)</label>
                    <input class="input" type="number" name="expected_income" value="{{ old('expected_income', auth()->user()->expected_income) }}" dir="ltr">
                </div>
                <div class="field">
                    <label>رقم الحساب الجاري (RIP)</label>
                    <input class="input" name="rip" value="{{ old('rip', auth()->user()->rip) }}" dir="ltr">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">إرسال طلب التحقق</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('wilaya-select')?.addEventListener('change', async function() {
    const sel = document.getElementById('commune-select');
    sel.innerHTML = '<option value="">جاري التحميل...</option>';
    if (!this.value) { sel.innerHTML = '<option value="">— اختر الولاية أولاً —</option>'; return; }
    try {
        const res = await fetch('/api/v1/wilayas/' + this.value + '/communes');
        const data = await res.json();
        sel.innerHTML = '<option value="">— اختر البلدية —</option>' + data.map(c => `<option value="${c.id}">${c.name_ar}</option>`).join('');
    } catch(e) { sel.innerHTML = '<option value="">خطأ في التحميل</option>'; }
});
</script>
@endpush

@endsection
