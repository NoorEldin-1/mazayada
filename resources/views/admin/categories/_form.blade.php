{{-- Shared category create/edit form. $category is null on create. --}}
@php($c = $category ?? null)

<div class="card card-pad" style="margin-bottom:1.5rem">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="field">
            <label for="name_ar">{{ __('admin.categories.f_name_ar') }} <span style="color:var(--red-600)">*</span></label>
            <input type="text" id="name_ar" name="name_ar" class="input" value="{{ old('name_ar', $c?->name_ar) }}" required>
            @error('name_ar') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="name_fr">{{ __('admin.categories.f_name_fr') }}</label>
            <input type="text" id="name_fr" name="name_fr" class="input" value="{{ old('name_fr', $c?->name_fr) }}">
            @error('name_fr') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="name_en">{{ __('admin.categories.f_name_en') }}</label>
            <input type="text" id="name_en" name="name_en" class="input" value="{{ old('name_en', $c?->name_en) }}" style="direction:ltr">
            @error('name_en') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="icon">{{ __('admin.categories.f_icon') }}</label>
            <input type="text" id="icon" name="icon" class="input" value="{{ old('icon', $c?->icon) }}" style="direction:ltr">
            @error('icon') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="field" style="margin-top:0.75rem">
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $c?->is_active ?? true) ? 'checked' : '' }}>
            {{ __('admin.categories.f_active') }}
        </label>
    </div>
</div>
