{{-- Shared publication-package create/edit form. $package is null on create. --}}
@php($p = $package)

<x-ui.card class="mb-6">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:1rem">
        <div class="field">
            <label for="code">{{ __('publication.f_code') }} <span class="text-danger">*</span></label>
            <input type="text" id="code" name="code" class="input" dir="ltr" value="{{ old('code', $p?->code) }}" required maxlength="40">
            @error('code') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="display_area">{{ __('publication.f_display_area') }} <span class="text-danger">*</span></label>
            <select id="display_area" name="display_area" class="select" required>
                @foreach(\App\Enums\PublicationArea::cases() as $area)
                    <option value="{{ $area->value }}" @selected(old('display_area', $p?->display_area?->value) === $area->value)>{{ $area->label() }}</option>
                @endforeach
            </select>
            @error('display_area') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        @foreach(['ar', 'fr', 'en'] as $loc)
            <div class="field">
                <label for="name_{{ $loc }}">{{ __('publication.f_name_'.$loc) }} @if($loc === 'ar')<span class="text-danger">*</span>@endif</label>
                <input type="text" id="name_{{ $loc }}" name="name_{{ $loc }}" class="input" @if($loc !== 'ar') dir="ltr" @endif value="{{ old('name_'.$loc, $p?->{'name_'.$loc}) }}" @if($loc === 'ar') required @endif>
                @error('name_'.$loc) <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
        @endforeach
        <div class="field">
            <label for="price">{{ __('publication.f_price') }} <span class="text-danger">*</span></label>
            <input type="number" id="price" name="price" class="input num" min="0" step="0.01" value="{{ old('price', $p ? $p->price / 100 : 0) }}" required>
            @error('price') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="priority_price">{{ __('publication.f_priority_price') }} <span class="text-danger">*</span></label>
            <input type="number" id="priority_price" name="priority_price" class="input num" min="0" step="0.01" value="{{ old('priority_price', $p ? $p->priority_price / 100 : 0) }}" required>
            @error('priority_price') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="duration_days">{{ __('publication.f_duration_days') }} <span class="text-danger">*</span></label>
            <input type="number" id="duration_days" name="duration_days" class="input num" min="1" max="365" value="{{ old('duration_days', $p?->duration_days ?? 30) }}" required>
            @error('duration_days') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="sort_order">{{ __('publication.f_sort') }}</label>
            <input type="number" id="sort_order" name="sort_order" class="input num" min="0" value="{{ old('sort_order', $p?->sort_order ?? 0) }}">
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:1rem;margin-top:1rem">
        @foreach(['ar', 'fr', 'en'] as $loc)
            <div class="field">
                <label for="description_{{ $loc }}">{{ __('publication.f_description_'.$loc) }}</label>
                <textarea id="description_{{ $loc }}" name="description_{{ $loc }}" class="input" rows="3" @if($loc !== 'ar') dir="ltr" @endif>{{ old('description_'.$loc, $p?->{'description_'.$loc}) }}</textarea>
            </div>
        @endforeach
    </div>

    <div class="field" style="margin-top:0.75rem">
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $p?->is_active ?? true) ? 'checked' : '' }}>
            {{ __('publication.f_active') }}
        </label>
    </div>
</x-ui.card>
