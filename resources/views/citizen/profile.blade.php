@extends('layouts.citizen')
@section('title', 'الملف الشخصي')
@section('content')

<h2 style="font-size:24px;font-weight:700;margin:0 0 20px">الملف الشخصي</h2>

@if(session('success'))
<div style="background:#E5F3EC;color:#1d6045;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:13px">{{ session('success') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    {{-- Identity Card --}}
    <div class="card">
        <div class="card-h"><h3>بطاقة الهوية</h3></div>
        <div class="card-pad">
            <div style="text-align:center;margin-bottom:20px">
                <div style="width:80px;height:80px;border-radius:20px;background:linear-gradient(135deg,#2D6A4F,#1B4D3E);color:#fff;display:grid;place-items:center;font-weight:700;font-size:28px;margin:0 auto 12px">{{ mb_substr(auth()->user()->first_name_ar, 0, 1) }}</div>
                <h3 style="margin:0 0 4px;font-size:18px">{{ auth()->user()->fullNameAr() }}</h3>
                <p style="color:var(--muted);font-size:13px;margin:0">{{ auth()->user()->email }}</p>
            </div>
            <div style="display:grid;gap:12px">
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line)">
                    <span style="color:var(--muted);font-size:13px">رقم التعريف الوطني</span>
                    <span class="num" style="font-size:13px;font-weight:600" dir="ltr">{{ auth()->user()->nin }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line)">
                    <span style="color:var(--muted);font-size:13px">الهاتف</span>
                    <span class="num" style="font-size:13px;font-weight:600" dir="ltr">{{ auth()->user()->phone }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line)">
                    <span style="color:var(--muted);font-size:13px">الدور</span>
                    <span class="chip chip-ok"><span class="dot"></span>{{ auth()->user()->role->label() }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line)">
                    <span style="color:var(--muted);font-size:13px">حالة التحقق</span>
                    <span class="chip {{ auth()->user()->kyc_status->chipClass() }}"><span class="dot"></span>{{ auth()->user()->kyc_status->label() }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0">
                    <span style="color:var(--muted);font-size:13px">تاريخ التسجيل</span>
                    <span class="num" style="font-size:13px">{{ auth()->user()->created_at->format('Y-m-d') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Profile --}}
    <div class="card">
        <div class="card-h"><h3>تعديل المعلومات</h3></div>
        <div class="card-pad">
            <form action="{{ route('citizen.profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="field" style="margin-bottom:14px">
                    <label>العنوان</label>
                    <input class="input" name="address" value="{{ old('address', auth()->user()->address) }}">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
                    <div class="field">
                        <label>الرمز البريدي</label>
                        <input class="input" name="postal_code" value="{{ old('postal_code', auth()->user()->postal_code) }}" dir="ltr" maxlength="5">
                    </div>
                    <div class="field">
                        <label>المهنة</label>
                        <input class="input" name="profession" value="{{ old('profession', auth()->user()->profession) }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">حفظ التعديلات</button>
            </form>
        </div>
    </div>
</div>

@endsection
