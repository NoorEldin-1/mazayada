@extends('layouts.admin')

@section('title', __('admin.auctions.edit_title'))
@section('page-title', __('admin.auctions.edit_title'))

@section('content')

<form method="POST" action="{{ route('admin.auctions.update', $auction) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- Section 1: Titles & Descriptions --}}
    <x-ui.card :title="__('admin.auctions.sec_titles')" class="mb-6">

        <div class="field">
            <label for="title_ar">{{ __('admin.auctions.f_title_ar') }} <span class="text-danger">*</span></label>
            <input type="text" id="title_ar" name="title_ar" class="input" value="{{ old('title_ar', $auction->title_ar) }}" required>
            @error('title_ar') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="title_fr">{{ __('admin.auctions.f_title_fr') }}</label>
            <input type="text" id="title_fr" name="title_fr" class="input" value="{{ old('title_fr', $auction->title_fr) }}">
            @error('title_fr') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="description_ar">{{ __('admin.auctions.f_description_ar') }} <span class="text-danger">*</span></label>
            <textarea id="description_ar" name="description_ar" class="textarea" rows="4" required>{{ old('description_ar', $auction->description_ar) }}</textarea>
            @error('description_ar') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="description_fr">{{ __('admin.auctions.f_description_fr') }}</label>
            <textarea id="description_fr" name="description_fr" class="textarea" rows="4">{{ old('description_fr', $auction->description_fr) }}</textarea>
            @error('description_fr') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
    </x-ui.card>

    {{-- Section 1b: Asset specifications — repeatable title + description blocks.
         Pre-filled from the stored set; the submit is the full desired list, so
         removing every row clears them on save. --}}
    <x-ui.card :title="__('admin.auctions.sec_specifications')" class="mb-6">
        <p style="font-size:0.85rem;color:var(--ink-muted);margin-bottom:1rem">{{ __('admin.auctions.specs_hint') }}</p>

        @php($specRows = old('specifications', $auction->specifications ?? []))
        <div id="specs-rows" data-next-index="{{ count($specRows) }}">
            @foreach($specRows as $i => $spec)
                @include('admin.auctions.partials.spec-row', ['index' => $i, 'spec' => $spec])
            @endforeach
        </div>

        <button type="button" id="specs-add" style="display:inline-flex;align-items:center;gap:6px;font-size:0.9rem;font-weight:600;color:var(--primary);background:none;border:1px dashed var(--line);border-radius:8px;padding:0.5rem 1rem;cursor:pointer">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ __('admin.auctions.spec_add') }}
        </button>

        <template id="spec-row-template">
            @include('admin.auctions.partials.spec-row', ['index' => '__INDEX__', 'spec' => []])
        </template>
    </x-ui.card>

    {{-- Section 2: Classification --}}
    <x-ui.card :title="__('admin.auctions.sec_classification')" class="mb-6">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="category_id">{{ __('admin.auctions.f_category') }} <span class="text-danger">*</span></label>
                <select id="category_id" name="category_id" class="select" required>
                    <option value="">{{ __('admin.auctions.choose_category') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $auction->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="wilaya_id">{{ __('admin.auctions.f_wilaya') }} <span class="text-danger">*</span></label>
                <select id="wilaya_id" name="wilaya_id" class="select" required data-selected-commune="{{ old('commune_id', $auction->commune_id) }}">
                    <option value="">{{ __('admin.auctions.choose_wilaya') }}</option>
                    @foreach($wilayas as $wilaya)
                        <option value="{{ $wilaya->id }}" {{ old('wilaya_id', $auction->wilaya_id) == $wilaya->id ? 'selected' : '' }}>{{ $wilaya->code }} - {{ $wilaya->name }}</option>
                    @endforeach
                </select>
                @error('wilaya_id') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="commune_id">{{ __('admin.auctions.f_commune') }}</label>
                <select id="commune_id" name="commune_id" class="select">
                    <option value="">{{ __('admin.auctions.choose_commune') }}</option>
                </select>
                @error('commune_id') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="mayor_name">{{ __('admin.auctions.f_mayor_name') }}</label>
                <input type="text" id="mayor_name" name="mayor_name" class="input" value="{{ old('mayor_name', $auction->mayor_name) }}">
                @error('mayor_name') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="auction_type">{{ __('admin.auctions.f_auction_type') }} <span class="text-danger">*</span></label>
                <select id="auction_type" name="auction_type" class="select" required>
                    <option value="">{{ __('admin.auctions.choose_type') }}</option>
                    <option value="SALE" {{ old('auction_type', $auction->auction_type?->value) === 'SALE' ? 'selected' : '' }}>{{ __('enums.auction_type.SALE') }}</option>
                    <option value="LEASE" {{ old('auction_type', $auction->auction_type?->value) === 'LEASE' ? 'selected' : '' }}>{{ __('enums.auction_type.LEASE') }}</option>
                </select>
                @error('auction_type') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="condition">{{ __('admin.auctions.f_condition') }} <span class="text-danger">*</span></label>
                <select id="condition" name="condition" class="select" required>
                    <option value="">{{ __('admin.auctions.choose_condition') }}</option>
                    <option value="NEW" {{ old('condition', $auction->condition?->value) === 'NEW' ? 'selected' : '' }}>{{ __('enums.asset_condition.NEW') }}</option>
                    <option value="GOOD" {{ old('condition', $auction->condition?->value) === 'GOOD' ? 'selected' : '' }}>{{ __('enums.asset_condition.GOOD') }}</option>
                    <option value="FAIR" {{ old('condition', $auction->condition?->value) === 'FAIR' ? 'selected' : '' }}>{{ __('enums.asset_condition.FAIR') }}</option>
                    <option value="POOR" {{ old('condition', $auction->condition?->value) === 'POOR' ? 'selected' : '' }}>{{ __('enums.asset_condition.POOR') }}</option>
                    <option value="SCRAP" {{ old('condition', $auction->condition?->value) === 'SCRAP' ? 'selected' : '' }}>{{ __('enums.asset_condition.SCRAP') }}</option>
                </select>
                @error('condition') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="asset_location">{{ __('admin.auctions.f_asset_location') }}</label>
                <input type="text" id="asset_location" name="asset_location" class="input" value="{{ old('asset_location', $auction->asset_location) }}">
                @error('asset_location') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="unit_count">{{ __('admin.auctions.f_unit_count') }}</label>
                <input type="number" id="unit_count" name="unit_count" class="input" value="{{ old('unit_count', $auction->unit_count) }}" min="1">
                @error('unit_count') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
        </div>

        <x-auctions.map-picker
            :lat="old('latitude', $auction->latitude)"
            :lng="old('longitude', $auction->longitude)" />

        <div class="field" style="margin-top:0.75rem">
            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                <input type="checkbox" name="requires_commerce_register" value="1" {{ old('requires_commerce_register', $auction->requires_commerce_register) ? 'checked' : '' }}>
                {{ __('admin.auctions.f_requires_cr') }}
            </label>
        </div>

        {{-- Lease-specific fields --}}
        <div id="lease-fields" style="display:none;margin-top:1rem">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div class="field">
                    <label for="lease_duration_years">{{ __('admin.auctions.f_lease_duration') }}</label>
                    <input type="number" id="lease_duration_years" name="lease_duration_years" class="input" value="{{ old('lease_duration_years', $auction->lease_duration_years) }}" min="1">
                    @error('lease_duration_years') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="lease_renewals">{{ __('admin.auctions.f_lease_renewals') }}</label>
                    <input type="number" id="lease_renewals" name="lease_renewals" class="input" value="{{ old('lease_renewals', $auction->lease_renewals) }}" min="0">
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
                        <option value="{{ $ac }}" {{ old('asset_class', $auction->asset_class?->value ?? 'MOVABLE') === $ac ? 'selected' : '' }}>{{ __('enums.asset_class.'.$ac) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="max_extensions">{{ __('admin.auctions.f_max_extensions') }}</label>
                <input type="number" id="max_extensions" name="max_extensions" class="input" value="{{ old('max_extensions', $auction->max_extensions) }}" min="0">
            </div>
            <div class="field">
                <label for="inspection_start">{{ __('admin.auctions.f_inspection_start') }}</label>
                <input type="datetime-local" id="inspection_start" name="inspection_start" class="input" value="{{ old('inspection_start', $auction->inspection_start?->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="field">
                <label for="inspection_end">{{ __('admin.auctions.f_inspection_end') }}</label>
                <input type="datetime-local" id="inspection_end" name="inspection_end" class="input" value="{{ old('inspection_end', $auction->inspection_end?->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="field">
                <label for="inspection_location">{{ __('admin.auctions.f_inspection_location') }}</label>
                <input type="text" id="inspection_location" name="inspection_location" class="input" value="{{ old('inspection_location', $auction->inspection_location) }}">
            </div>
            <div class="field">
                <label for="original_owner_nin">{{ __('admin.auctions.f_original_owner_nin') }}</label>
                <input type="text" id="original_owner_nin" name="original_owner_nin" class="input num" value="{{ old('original_owner_nin', $auction->original_owner_nin) }}" maxlength="18" placeholder="18">
            </div>
        </div>
    </x-ui.card>

    {{-- Section 2c: Photos + short video (spec §4 step 1) --}}
    <x-ui.card :title="__('admin.auctions.sec_photos')" class="mb-6">
        {{-- Sub-section: images --}}
        <h3 style="font-size:0.9rem;font-weight:600;color:var(--ink);margin-block-end:0.75rem">{{ __('admin.auctions.sec_photos_images') }}</h3>
        @if($auction->photosArray())
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-block-end:12px">
                @foreach($auction->photosArray() as $p)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($p) }}" style="width:90px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--line)">
                @endforeach
            </div>
        @endif
        <div class="field">
            <label for="photos">{{ __('admin.auctions.f_photos') }}</label>
            <input type="file" id="photos" name="photos[]" class="input" accept="image/jpeg,image/png,image/webp" multiple>
            <small style="color:var(--ink-muted)">{{ __('admin.auctions.photos_hint') }}</small>
            @error('photos.*') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        <div id="photos-preview" style="display:flex;gap:8px;flex-wrap:wrap;margin-block-start:12px"></div>

        {{-- Sub-section: a single short asset video (1–2 min, MP4, ≤50 MB) --}}
        <hr style="border:0;border-top:1px solid var(--line);margin-block:1.25rem">
        <h3 style="font-size:0.9rem;font-weight:600;color:var(--ink);margin-block-end:0.75rem">{{ __('admin.auctions.sec_photos_video') }}</h3>
        @if($auction->videoUrl())
            <video controls src="{{ $auction->videoUrl() }}" style="max-width:320px;width:100%;border-radius:8px;border:1px solid var(--line);margin-block-end:12px"></video>
        @endif
        <div class="field">
            <label for="video">{{ __('admin.auctions.f_video') }}</label>
            <input type="file" id="video" name="video" class="input" accept="video/mp4"
                   data-err-type="{{ __('admin.auctions.video_err_type') }}"
                   data-err-size="{{ __('admin.auctions.video_err_size') }}"
                   data-err-duration="{{ __('admin.auctions.video_err_duration') }}">
            <small style="color:var(--ink-muted)">{{ __('admin.auctions.video_hint') }}</small>
            <small id="video-error" class="text-danger text-xs mt-1" style="display:none"></small>
            @error('video') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        <div id="video-preview" style="margin-block-start:12px;display:none">
            <video controls style="max-width:320px;width:100%;border-radius:8px;border:1px solid var(--line)"></video>
        </div>
    </x-ui.card>

    {{-- Section 3: Pricing --}}
    <x-ui.card :title="__('admin.auctions.sec_pricing')" class="mb-6">
        <p style="font-size:0.85rem;color:var(--ink-muted);margin-bottom:1rem">{{ __('admin.auctions.pricing_note') }}</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="opening_price">{{ __('admin.auctions.f_opening_price') }} <span class="text-danger">*</span></label>
                <input type="number" id="opening_price" name="opening_price" class="input num" value="{{ old('opening_price', $auction->opening_price / 100) }}" min="0" step="0.01" required>
                @error('opening_price') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="deposit_percent">{{ __('admin.auctions.f_deposit_percent') }}</label>
                <input type="number" id="deposit_percent" name="deposit_percent" class="input num" value="{{ old('deposit_percent', rtrim(rtrim(number_format((float) $auction->deposit_percent, 2, '.', ''), '0'), '.')) }}" min="0" max="100" step="0.01">
                <small style="color:var(--ink-muted)">{{ __('admin.auctions.deposit_percent_hint') }}</small>
                <small id="deposit-preview" class="num" data-prefix="{{ __('admin.auctions.deposit_computed_prefix') }}" data-currency="{{ __('common.currency') }}" style="color:var(--primary);font-weight:600;display:block;margin-block-start:4px"></small>
                @error('deposit_percent') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field" style="grid-column:1 / -1">
                <label for="book_price">{{ __('admin.auctions.f_book_price') }}</label>
                <input type="number" id="book_price" name="book_price" class="input num" value="{{ old('book_price', $auction->book_price ? $auction->book_price / 100 : '') }}" min="0" step="0.01">
                <small style="color:var(--ink-muted)">{{ __('admin.auctions.book_price_hint') }}</small>
                @error('book_price') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
        </div>
    </x-ui.card>

    {{-- Section 4: Scheduling --}}
    <x-ui.card :title="__('admin.auctions.sec_scheduling')" class="mb-6">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="start_time">{{ __('admin.auctions.f_start_time') }} <span class="text-danger">*</span></label>
                <input type="datetime-local" id="start_time" name="start_time" class="input" value="{{ old('start_time', $auction->start_time?->format('Y-m-d\TH:i')) }}" required>
                @error('start_time') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="end_time">{{ __('admin.auctions.f_end_time') }} <span class="text-danger">*</span></label>
                <input type="datetime-local" id="end_time" name="end_time" class="input" value="{{ old('end_time', $auction->end_time?->format('Y-m-d\TH:i')) }}" required>
                @error('end_time') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
        </div>
    </x-ui.card>

    {{-- Entity & responsible staff. Only a SUPER_ADMIN may reassign the entity;
         entity staff cannot move it but may still tag a responsible colleague. --}}
    <x-ui.card :title="__('admin.auctions.sec_entity')" class="mb-6">
        @if(auth()->user()->hasRole('SUPER_ADMIN'))
        <div class="field">
            <label for="entity_id">{{ __('admin.auctions.f_entity') }} <span class="text-danger">*</span></label>
            <select id="entity_id" name="entity_id" class="select" required data-selected-staff="{{ old('entity_user_id', $auction->entity_user_id) }}">
                <option value="">{{ __('admin.auctions.choose_entity') }}</option>
                @foreach($entities as $entity)
                    <option value="{{ $entity->id }}" {{ old('entity_id', $auction->entity_id) == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                @endforeach
            </select>
            @error('entity_id') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="entity_user_id">{{ __('admin.auctions.f_entity_user') }}</label>
            <select id="entity_user_id" name="entity_user_id" class="select">
                <option value="">{{ __('admin.auctions.choose_entity_user') }}</option>
            </select>
            @error('entity_user_id') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        @else
        <div class="field">
            <label for="entity_user_id">{{ __('admin.auctions.f_entity_user') }}</label>
            <select id="entity_user_id" name="entity_user_id" class="select">
                <option value="">{{ __('admin.auctions.choose_entity_user') }}</option>
                @foreach($entityUsers as $staff)
                    <option value="{{ $staff->id }}" {{ old('entity_user_id', $auction->entity_user_id) == $staff->id ? 'selected' : '' }}>{{ $staff->full_name }}</option>
                @endforeach
            </select>
            @error('entity_user_id') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        @endif
    </x-ui.card>

    {{-- Submit --}}
    <x-ui.btn variant="primary" size="lg" class="w-full">{{ __('admin.auctions.submit_edit') }}</x-ui.btn>
</form>

{{-- §4 step 2 — generate the signed condition book (separate form; cannot nest) --}}
@can('documents.generate')
<x-ui.card :title="__('admin.auctions.sec_documents')" class="mt-6">
    <p style="font-size:0.85rem;color:var(--ink-muted);margin-bottom:1rem">{{ __('admin.auctions.gen_condition_book_hint') }}</p>
    <form method="POST" action="{{ route('admin.auctions.condition-book', $auction) }}">
        @csrf
        <x-ui.btn variant="ghost">{{ __('admin.auctions.gen_condition_book') }}</x-ui.btn>
    </form>
    @if($auction->documents()->where('type','CONDITION_BOOK')->exists())
        <div style="margin-top:0.75rem;font-size:0.85rem">
            @foreach($auction->documents()->where('type','CONDITION_BOOK')->latest()->get() as $doc)
                <a href="{{ route('documents.download', $doc) }}" style="display:block;margin-bottom:4px">↓ {{ $doc->title }}</a>
            @endforeach
        </div>
    @endif
</x-ui.card>
@endcan

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Lease fields toggle ---
        var typeSelect = document.getElementById('auction_type');
        var leaseFields = document.getElementById('lease-fields');
        function toggleLease() {
            leaseFields.style.display = typeSelect.value === 'LEASE' ? 'block' : 'none';
        }
        typeSelect.addEventListener('change', toggleLease);
        toggleLease();

        // --- Wilaya → Commune cascade ---
        var wilaya = document.getElementById('wilaya_id');
        var commune = document.getElementById('commune_id');
        if (wilaya && commune) {
            var preselectCommune = wilaya.getAttribute('data-selected-commune');
            async function loadCommunes(selected) {
                commune.innerHTML = '<option value="">{{ __('admin.auctions.choose_commune') }}</option>';
                if (!wilaya.value) return;
                try {
                    var res = await fetch('/api/v1/wilayas/' + wilaya.value + '/communes');
                    var data = await res.json();
                    (data.data ?? data).forEach(function (c) {
                        var opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = (c.name_ar ?? c.name) + (c.postal_code ? ' (' + c.postal_code + ')' : '');
                        if (selected && String(selected) === String(c.id)) opt.selected = true;
                        commune.appendChild(opt);
                    });
                } catch (e) { /* network error — leave commune empty */ }
            }
            wilaya.addEventListener('change', function () { loadCommunes(null); });
            if (wilaya.value) loadCommunes(preselectCommune);
        }

        // --- Image preview (newly chosen files) ---
        var photos = document.getElementById('photos');
        var photosPreview = document.getElementById('photos-preview');
        if (photos && photosPreview) {
            photos.addEventListener('change', function () {
                photosPreview.innerHTML = '';
                Array.prototype.forEach.call(photos.files, function (file) {
                    if (!file.type.startsWith('image/')) return;
                    var img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.onload = function () { URL.revokeObjectURL(img.src); };
                    img.style.cssText = 'width:90px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--line)';
                    photosPreview.appendChild(img);
                });
            });
        }

        // --- Single short video: strict type/size + 1–2 min duration (client gate) ---
        var video = document.getElementById('video');
        var videoError = document.getElementById('video-error');
        var videoPreview = document.getElementById('video-preview');
        var videoValid = true;
        if (video) {
            var MAX_BYTES = 50 * 1024 * 1024; // 50 MB
            var MAX_SEC = 120; // up to 2 minutes; any shorter length is fine
            function showVideoError(msg) {
                videoValid = false;
                video.value = '';
                if (videoPreview) videoPreview.style.display = 'none';
                videoError.textContent = msg;
                videoError.style.display = 'block';
            }
            function clearVideoError() {
                videoValid = true;
                videoError.textContent = '';
                videoError.style.display = 'none';
            }
            video.addEventListener('change', function () {
                clearVideoError();
                var file = video.files[0];
                if (!file) { if (videoPreview) videoPreview.style.display = 'none'; return; }
                if (file.type !== 'video/mp4') { showVideoError(video.dataset.errType); return; }
                if (file.size > MAX_BYTES) { showVideoError(video.dataset.errSize); return; }
                var url = URL.createObjectURL(file);
                var probe = document.createElement('video');
                probe.preload = 'metadata';
                probe.onloadedmetadata = function () {
                    var dur = probe.duration;
                    if (!isNaN(dur) && dur > MAX_SEC) {
                        URL.revokeObjectURL(url);
                        showVideoError(video.dataset.errDuration);
                        return;
                    }
                    if (videoPreview) {
                        videoPreview.querySelector('video').src = url;
                        videoPreview.style.display = 'block';
                    }
                };
                probe.onerror = function () {
                    URL.revokeObjectURL(url);
                    showVideoError(video.dataset.errType);
                };
                probe.src = url;
            });
        }

        // --- Entity → staff cascade (SUPER_ADMIN only; others are server-rendered) ---
        var entity = document.getElementById('entity_id');
        var entityUser = document.getElementById('entity_user_id');
        if (entity && entityUser) {
            var preselectStaff = entity.getAttribute('data-selected-staff');
            async function loadStaff(selected) {
                entityUser.innerHTML = '<option value="">{{ __('admin.auctions.choose_entity_user') }}</option>';
                if (!entity.value) return;
                try {
                    var res = await fetch('/admin/entities/' + entity.value + '/staff');
                    if (!res.ok) return;
                    var data = await res.json();
                    (data.data ?? data).forEach(function (s) {
                        var opt = document.createElement('option');
                        opt.value = s.id;
                        opt.textContent = s.full_name;
                        if (selected && String(selected) === String(s.id)) opt.selected = true;
                        entityUser.appendChild(opt);
                    });
                } catch (e) { /* network error — leave staff empty */ }
            }
            entity.addEventListener('change', function () { loadStaff(null); });
            if (entity.value) loadStaff(preselectStaff);
        }

        // --- Repeatable asset specifications: add / remove rows ---
        (function () {
            var wrap = document.getElementById('specs-rows');
            var addBtn = document.getElementById('specs-add');
            var tpl = document.getElementById('spec-row-template');
            if (!wrap || !addBtn || !tpl) return;
            var nextIndex = parseInt(wrap.dataset.nextIndex || '0', 10);
            function addRow() {
                var html = tpl.innerHTML.replace(/__INDEX__/g, String(nextIndex));
                nextIndex++;
                var temp = document.createElement('div');
                temp.innerHTML = html.trim();
                var row = temp.querySelector('.spec-row');
                if (row) wrap.appendChild(row);
            }
            addBtn.addEventListener('click', addRow);
            wrap.addEventListener('click', function (e) {
                var btn = e.target.closest('.spec-remove');
                if (!btn) return;
                var row = btn.closest('.spec-row');
                if (row) row.remove();
            });
            // Start with one blank row when empty (the server prunes it if untouched).
            if (!wrap.querySelector('.spec-row')) addRow();
        })();

        // --- Block submit while the chosen video is invalid ---
        var form = video ? video.closest('form') : null;
        if (form) {
            form.addEventListener('submit', function (e) {
                if (!videoValid) {
                    e.preventDefault();
                    if (videoError) videoError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }

        // --- Live participation-deposit preview (opening_price × percent) ---
        @include('admin.auctions.partials.deposit-preview-js')
    });
</script>
@endpush
