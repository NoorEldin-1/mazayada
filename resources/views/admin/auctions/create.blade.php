@extends('layouts.admin')

@section('title', __('admin.auctions.create_title'))
@section('page-title', __('admin.auctions.create_title'))

@section('content')

<form method="POST" action="{{ route('admin.auctions.store') }}" enctype="multipart/form-data">
    @csrf

    {{-- Section 1: Titles & Descriptions --}}
    <x-ui.card :title="__('admin.auctions.sec_titles')" class="mb-6">

        <div class="field">
            <label for="title_ar">{{ __('admin.auctions.f_title_ar') }} <span class="text-danger">*</span></label>
            <input type="text" id="title_ar" name="title_ar" class="input" value="{{ old('title_ar') }}" required>
            @error('title_ar') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="title_fr">{{ __('admin.auctions.f_title_fr') }}</label>
            <input type="text" id="title_fr" name="title_fr" class="input" value="{{ old('title_fr') }}">
            @error('title_fr') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="description_ar">{{ __('admin.auctions.f_description_ar') }} <span class="text-danger">*</span></label>
            <textarea id="description_ar" name="description_ar" class="textarea" rows="4" required>{{ old('description_ar') }}</textarea>
            @error('description_ar') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="description_fr">{{ __('admin.auctions.f_description_fr') }}</label>
            <textarea id="description_fr" name="description_fr" class="textarea" rows="4">{{ old('description_fr') }}</textarea>
            @error('description_fr') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
    </x-ui.card>

    {{-- Section 2: Classification --}}
    <x-ui.card :title="__('admin.auctions.sec_classification')" class="mb-6">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="category_id">{{ __('admin.auctions.f_category') }} <span class="text-danger">*</span></label>
                <select id="category_id" name="category_id" class="select" required>
                    <option value="">{{ __('admin.auctions.choose_category') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="wilaya_id">{{ __('admin.auctions.f_wilaya') }} <span class="text-danger">*</span></label>
                <select id="wilaya_id" name="wilaya_id" class="select" required>
                    <option value="">{{ __('admin.auctions.choose_wilaya') }}</option>
                    @foreach($wilayas as $wilaya)
                        <option value="{{ $wilaya->id }}" {{ old('wilaya_id') == $wilaya->id ? 'selected' : '' }}>{{ $wilaya->code }} - {{ $wilaya->name }}</option>
                    @endforeach
                </select>
                @error('wilaya_id') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="auction_type">{{ __('admin.auctions.f_auction_type') }} <span class="text-danger">*</span></label>
                <select id="auction_type" name="auction_type" class="select" required>
                    <option value="">{{ __('admin.auctions.choose_type') }}</option>
                    <option value="SALE" {{ old('auction_type') === 'SALE' ? 'selected' : '' }}>{{ __('enums.auction_type.SALE') }}</option>
                    <option value="LEASE" {{ old('auction_type') === 'LEASE' ? 'selected' : '' }}>{{ __('enums.auction_type.LEASE') }}</option>
                </select>
                @error('auction_type') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="condition">{{ __('admin.auctions.f_condition') }} <span class="text-danger">*</span></label>
                <select id="condition" name="condition" class="select" required>
                    <option value="">{{ __('admin.auctions.choose_condition') }}</option>
                    <option value="NEW" {{ old('condition') === 'NEW' ? 'selected' : '' }}>{{ __('enums.asset_condition.NEW') }}</option>
                    <option value="GOOD" {{ old('condition') === 'GOOD' ? 'selected' : '' }}>{{ __('enums.asset_condition.GOOD') }}</option>
                    <option value="FAIR" {{ old('condition') === 'FAIR' ? 'selected' : '' }}>{{ __('enums.asset_condition.FAIR') }}</option>
                    <option value="POOR" {{ old('condition') === 'POOR' ? 'selected' : '' }}>{{ __('enums.asset_condition.POOR') }}</option>
                    <option value="SCRAP" {{ old('condition') === 'SCRAP' ? 'selected' : '' }}>{{ __('enums.asset_condition.SCRAP') }}</option>
                </select>
                @error('condition') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="asset_location">{{ __('admin.auctions.f_asset_location') }}</label>
                <input type="text" id="asset_location" name="asset_location" class="input" value="{{ old('asset_location') }}">
                @error('asset_location') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="unit_count">{{ __('admin.auctions.f_unit_count') }}</label>
                <input type="number" id="unit_count" name="unit_count" class="input" value="{{ old('unit_count', 1) }}" min="1">
                @error('unit_count') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="field" style="margin-top:0.75rem">
            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                <input type="checkbox" name="requires_commerce_register" value="1" {{ old('requires_commerce_register') ? 'checked' : '' }}>
                {{ __('admin.auctions.f_requires_cr') }}
            </label>
        </div>

        {{-- Lease-specific fields --}}
        <div id="lease-fields" style="display:none;margin-top:1rem">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div class="field">
                    <label for="lease_duration_years">{{ __('admin.auctions.f_lease_duration') }}</label>
                    <input type="number" id="lease_duration_years" name="lease_duration_years" class="input" value="{{ old('lease_duration_years') }}" min="1">
                    @error('lease_duration_years') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="lease_renewals">{{ __('admin.auctions.f_lease_renewals') }}</label>
                    <input type="number" id="lease_renewals" name="lease_renewals" class="input" value="{{ old('lease_renewals') }}" min="0">
                    @error('lease_renewals') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- Section 2b: Lifecycle (spec §2/§4) --}}
    <x-ui.card :title="__('admin.auctions.sec_lifecycle')" class="mb-6">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="asset_class">{{ __('admin.auctions.f_asset_class') }}</label>
                <select id="asset_class" name="asset_class" class="select">
                    @foreach(['MOVABLE','REAL_ESTATE','CUSTOMS'] as $ac)
                        <option value="{{ $ac }}" {{ old('asset_class', 'MOVABLE') === $ac ? 'selected' : '' }}>{{ __('enums.asset_class.'.$ac) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="max_extensions">{{ __('admin.auctions.f_max_extensions') }}</label>
                <input type="number" id="max_extensions" name="max_extensions" class="input" value="{{ old('max_extensions', 10) }}" min="0">
            </div>
            <div class="field">
                <label for="inspection_start">{{ __('admin.auctions.f_inspection_start') }}</label>
                <input type="datetime-local" id="inspection_start" name="inspection_start" class="input" value="{{ old('inspection_start') }}">
            </div>
            <div class="field">
                <label for="inspection_end">{{ __('admin.auctions.f_inspection_end') }}</label>
                <input type="datetime-local" id="inspection_end" name="inspection_end" class="input" value="{{ old('inspection_end') }}">
            </div>
            <div class="field">
                <label for="inspection_location">{{ __('admin.auctions.f_inspection_location') }}</label>
                <input type="text" id="inspection_location" name="inspection_location" class="input" value="{{ old('inspection_location') }}">
            </div>
            <div class="field">
                <label for="original_owner_nin">{{ __('admin.auctions.f_original_owner_nin') }}</label>
                <input type="text" id="original_owner_nin" name="original_owner_nin" class="input num" value="{{ old('original_owner_nin') }}" maxlength="18">
            </div>
        </div>
    </x-ui.card>

    {{-- Section 2c: Photos (spec §4 step 1) --}}
    <x-ui.card :title="__('admin.auctions.sec_photos')" class="mb-6">
        <div class="field">
            <label for="photos">{{ __('admin.auctions.f_photos') }}</label>
            <input type="file" id="photos" name="photos[]" class="input" accept="image/jpeg,image/png,image/webp" multiple>
            <small style="color:var(--ink-muted)">{{ __('admin.auctions.photos_hint') }}</small>
            @error('photos.*') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
    </x-ui.card>

    {{-- Section 3: Pricing --}}
    <x-ui.card :title="__('admin.auctions.sec_pricing')" class="mb-6">
        <p style="font-size:0.85rem;color:var(--ink-muted);margin-bottom:1rem">{{ __('admin.auctions.pricing_note') }}</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="opening_price">{{ __('admin.auctions.f_opening_price') }} <span class="text-danger">*</span></label>
                <input type="number" id="opening_price" name="opening_price" class="input num" value="{{ old('opening_price') }}" min="0" step="0.01" required>
                @error('opening_price') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="deposit_amount">{{ __('admin.auctions.f_deposit') }} <span class="text-danger">*</span></label>
                <input type="number" id="deposit_amount" name="deposit_amount" class="input num" value="{{ old('deposit_amount') }}" min="0" step="0.01" required>
                @error('deposit_amount') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="entry_fee">{{ __('admin.auctions.f_entry_fee') }}</label>
                <input type="number" id="entry_fee" name="entry_fee" class="input num" value="{{ old('entry_fee') }}" min="0" step="0.01">
                @error('entry_fee') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="book_price">{{ __('admin.auctions.f_book_price') }}</label>
                <input type="number" id="book_price" name="book_price" class="input num" value="{{ old('book_price') }}" min="0" step="0.01">
                @error('book_price') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
        </div>
    </x-ui.card>

    {{-- Section 4: Scheduling --}}
    <x-ui.card :title="__('admin.auctions.sec_scheduling')" class="mb-6">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="start_time">{{ __('admin.auctions.f_start_time') }} <span class="text-danger">*</span></label>
                <input type="datetime-local" id="start_time" name="start_time" class="input" value="{{ old('start_time') }}" required>
                @error('start_time') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="end_time">{{ __('admin.auctions.f_end_time') }} <span class="text-danger">*</span></label>
                <input type="datetime-local" id="end_time" name="end_time" class="input" value="{{ old('end_time') }}" required>
                @error('end_time') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
        </div>
    </x-ui.card>

    {{-- Entity — only a SUPER_ADMIN chooses; entity staff are pinned to their own. --}}
    @if(auth()->user()->hasRole('SUPER_ADMIN'))
    <x-ui.card :title="__('admin.auctions.sec_entity')" class="mb-6">
        <div class="field">
            <label for="entity_id">{{ __('admin.auctions.f_entity') }} <span class="text-danger">*</span></label>
            <select id="entity_id" name="entity_id" class="select" required>
                <option value="">{{ __('admin.auctions.choose_entity') }}</option>
                @foreach($entities as $entity)
                    <option value="{{ $entity->id }}" {{ old('entity_id') == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                @endforeach
            </select>
            @error('entity_id') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
    </x-ui.card>
    @endif

    {{-- Submit --}}
    <x-ui.btn variant="primary" size="lg" class="w-full">{{ __('admin.auctions.submit_create') }}</x-ui.btn>
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
