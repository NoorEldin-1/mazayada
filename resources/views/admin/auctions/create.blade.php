@extends('layouts.admin')

@section('title', 'إنشاء مزايدة جديدة')
@section('page-title', 'إنشاء مزايدة جديدة')

@section('content')

<form method="POST" action="{{ route('admin.auctions.store') }}">
    @csrf

    {{-- Section 1: Titles & Descriptions --}}
    <div class="card card-pad" style="margin-bottom:1.5rem">
        <h3 class="card-h">العناوين والأوصاف</h3>

        <div class="field">
            <label for="title_ar">العنوان بالعربية <span style="color:var(--red-600)">*</span></label>
            <input type="text" id="title_ar" name="title_ar" class="input" value="{{ old('title_ar') }}" required>
            @error('title_ar') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="title_fr">العنوان بالفرنسية</label>
            <input type="text" id="title_fr" name="title_fr" class="input" value="{{ old('title_fr') }}">
            @error('title_fr') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="description_ar">الوصف بالعربية <span style="color:var(--red-600)">*</span></label>
            <textarea id="description_ar" name="description_ar" class="textarea" rows="4" required>{{ old('description_ar') }}</textarea>
            @error('description_ar') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="description_fr">الوصف بالفرنسية</label>
            <textarea id="description_fr" name="description_fr" class="textarea" rows="4">{{ old('description_fr') }}</textarea>
            @error('description_fr') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
    </div>

    {{-- Section 2: Classification --}}
    <div class="card card-pad" style="margin-bottom:1.5rem">
        <h3 class="card-h">التصنيف</h3>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="category_id">الفئة <span style="color:var(--red-600)">*</span></label>
                <select id="category_id" name="category_id" class="select" required>
                    <option value="">— اختر الفئة —</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name_ar }}</option>
                    @endforeach
                </select>
                @error('category_id') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="wilaya_id">الولاية <span style="color:var(--red-600)">*</span></label>
                <select id="wilaya_id" name="wilaya_id" class="select" required>
                    <option value="">— اختر الولاية —</option>
                    @foreach($wilayas as $wilaya)
                        <option value="{{ $wilaya->id }}" {{ old('wilaya_id') == $wilaya->id ? 'selected' : '' }}>{{ $wilaya->code }} - {{ $wilaya->name_ar }}</option>
                    @endforeach
                </select>
                @error('wilaya_id') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="auction_type">نوع المزايدة <span style="color:var(--red-600)">*</span></label>
                <select id="auction_type" name="auction_type" class="select" required>
                    <option value="">— اختر النوع —</option>
                    <option value="SALE" {{ old('auction_type') === 'SALE' ? 'selected' : '' }}>بيع</option>
                    <option value="LEASE" {{ old('auction_type') === 'LEASE' ? 'selected' : '' }}>إيجار</option>
                </select>
                @error('auction_type') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="condition">حالة الأصل <span style="color:var(--red-600)">*</span></label>
                <select id="condition" name="condition" class="select" required>
                    <option value="">— اختر الحالة —</option>
                    <option value="NEW" {{ old('condition') === 'NEW' ? 'selected' : '' }}>جديد</option>
                    <option value="GOOD" {{ old('condition') === 'GOOD' ? 'selected' : '' }}>جيد</option>
                    <option value="FAIR" {{ old('condition') === 'FAIR' ? 'selected' : '' }}>مقبول</option>
                    <option value="POOR" {{ old('condition') === 'POOR' ? 'selected' : '' }}>سيئ</option>
                    <option value="SCRAP" {{ old('condition') === 'SCRAP' ? 'selected' : '' }}>خردة</option>
                </select>
                @error('condition') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="asset_location">موقع الأصل</label>
                <input type="text" id="asset_location" name="asset_location" class="input" value="{{ old('asset_location') }}">
                @error('asset_location') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="unit_count">عدد الوحدات</label>
                <input type="number" id="unit_count" name="unit_count" class="input" value="{{ old('unit_count', 1) }}" min="1">
                @error('unit_count') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="field" style="margin-top:0.75rem">
            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                <input type="checkbox" name="requires_commerce_register" value="1" {{ old('requires_commerce_register') ? 'checked' : '' }}>
                يتطلب سجل تجاري
            </label>
        </div>

        {{-- Lease-specific fields --}}
        <div id="lease-fields" style="display:none;margin-top:1rem">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div class="field">
                    <label for="lease_duration_years">مدة الإيجار (سنوات)</label>
                    <input type="number" id="lease_duration_years" name="lease_duration_years" class="input" value="{{ old('lease_duration_years') }}" min="1">
                    @error('lease_duration_years') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="lease_renewals">عدد التجديدات</label>
                    <input type="number" id="lease_renewals" name="lease_renewals" class="input" value="{{ old('lease_renewals') }}" min="0">
                    @error('lease_renewals') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Section 3: Pricing --}}
    <div class="card card-pad" style="margin-bottom:1.5rem">
        <h3 class="card-h">التسعير (بالدينار)</h3>
        <p style="font-size:0.85rem;color:var(--ink-muted);margin-bottom:1rem">يتم التحويل تلقائيا للسنتيم عند الإرسال</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="opening_price">السعر الافتتاحي <span style="color:var(--red-600)">*</span></label>
                <input type="number" id="opening_price" name="opening_price" class="input num" value="{{ old('opening_price') }}" min="0" step="0.01" required>
                @error('opening_price') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="deposit_amount">مبلغ الضمان <span style="color:var(--red-600)">*</span></label>
                <input type="number" id="deposit_amount" name="deposit_amount" class="input num" value="{{ old('deposit_amount') }}" min="0" step="0.01" required>
                @error('deposit_amount') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="entry_fee">رسوم المشاركة</label>
                <input type="number" id="entry_fee" name="entry_fee" class="input num" value="{{ old('entry_fee') }}" min="0" step="0.01">
                @error('entry_fee') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="book_price">ثمن دفتر الشروط</label>
                <input type="number" id="book_price" name="book_price" class="input num" value="{{ old('book_price') }}" min="0" step="0.01">
                @error('book_price') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>
        </div>
    </div>

    {{-- Section 4: Scheduling --}}
    <div class="card card-pad" style="margin-bottom:1.5rem">
        <h3 class="card-h">الجدولة</h3>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="start_time">وقت البدء <span style="color:var(--red-600)">*</span></label>
                <input type="datetime-local" id="start_time" name="start_time" class="input" value="{{ old('start_time') }}" required>
                @error('start_time') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="end_time">وقت الانتهاء <span style="color:var(--red-600)">*</span></label>
                <input type="datetime-local" id="end_time" name="end_time" class="input" value="{{ old('end_time') }}" required>
                @error('end_time') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>
        </div>
    </div>

    {{-- Entity --}}
    <div class="card card-pad" style="margin-bottom:1.5rem">
        <h3 class="card-h">الجهة</h3>
        <div class="field">
            <label for="entity_id">الجهة المنظمة <span style="color:var(--red-600)">*</span></label>
            <select id="entity_id" name="entity_id" class="select" required>
                <option value="">— اختر الجهة —</option>
                @foreach($entities as $entity)
                    <option value="{{ $entity->id }}" {{ old('entity_id') == $entity->id ? 'selected' : '' }}>{{ $entity->name_ar }}</option>
                @endforeach
            </select>
            @error('entity_id') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
    </div>

    {{-- Submit --}}
    <button type="submit" class="btn btn-primary btn-block btn-lg">إنشاء المزايدة</button>
</form>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var typeSelect = document.getElementById('auction_type');
        var leaseFields = document.getElementById('lease-fields');

        function toggleLease() {
            leaseFields.style.display = typeSelect.value === 'LEASE' ? 'block' : 'none';
        }

        typeSelect.addEventListener('change', toggleLease);
        toggleLease();
    });
</script>
@endpush
