{{-- Shared entity create/edit form. $entity is null on create. --}}
@php($e = $entity ?? null)

<div class="card card-pad" style="margin-bottom:1.5rem">
    <h3 class="card-h">{{ __('admin.entities.sec_identity') }}</h3>

    <div class="field">
        <label for="name">{{ __('admin.entities.f_name_internal') }} <span style="color:var(--red-600)">*</span></label>
        <input type="text" id="name" name="name" class="input" value="{{ old('name', $e?->getRawOriginal('name')) }}" required>
        @error('name') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="field">
            <label for="name_ar">{{ __('admin.entities.f_name_ar') }} <span style="color:var(--red-600)">*</span></label>
            <input type="text" id="name_ar" name="name_ar" class="input" value="{{ old('name_ar', $e?->name_ar) }}" required>
            @error('name_ar') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="name_fr">{{ __('admin.entities.f_name_fr') }}</label>
            <input type="text" id="name_fr" name="name_fr" class="input" value="{{ old('name_fr', $e?->name_fr) }}">
            @error('name_fr') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="field">
        <label for="type">{{ __('admin.entities.f_type') }} <span style="color:var(--red-600)">*</span></label>
        <select id="type" name="type" class="select" required>
            <option value="">{{ __('admin.entities.choose_type') }}</option>
            @foreach($types as $type)
                <option value="{{ $type->value }}" {{ old('type', $e?->type?->value) === $type->value ? 'selected' : '' }}>
                    {{ $type->code() }} — {{ $type->label() }}
                </option>
            @endforeach
        </select>
        @error('type') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
    </div>
</div>

<div class="card card-pad" style="margin-bottom:1.5rem">
    <h3 class="card-h">{{ __('admin.entities.sec_location') }}</h3>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="field">
            <label for="wilaya_id">{{ __('admin.entities.f_wilaya') }} <span style="color:var(--red-600)">*</span></label>
            <select id="wilaya_id" name="wilaya_id" class="select" required data-selected-commune="{{ old('commune_id', $e?->commune_id) }}">
                <option value="">{{ __('admin.entities.choose_wilaya') }}</option>
                @foreach($wilayas as $wilaya)
                    <option value="{{ $wilaya->id }}" {{ (string) old('wilaya_id', $e?->wilaya_id) === (string) $wilaya->id ? 'selected' : '' }}>
                        {{ $wilaya->code }} - {{ $wilaya->name }}
                    </option>
                @endforeach
            </select>
            @error('wilaya_id') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="commune_id">{{ __('admin.entities.f_commune') }}</label>
            <select id="commune_id" name="commune_id" class="select">
                <option value="">{{ __('admin.entities.choose_commune') }}</option>
            </select>
            @error('commune_id') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="field">
        <label for="address">{{ __('admin.entities.f_address') }}</label>
        <input type="text" id="address" name="address" class="input" value="{{ old('address', $e?->address) }}">
        @error('address') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="field">
            <label for="phone">{{ __('admin.entities.f_phone') }}</label>
            <input type="text" id="phone" name="phone" class="input" value="{{ old('phone', $e?->phone) }}" style="direction:ltr">
            @error('phone') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="email">{{ __('admin.entities.f_email') }}</label>
            <input type="email" id="email" name="email" class="input" value="{{ old('email', $e?->email) }}" style="direction:ltr">
            @error('email') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="field" style="margin-top:0.75rem">
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $e?->is_active ?? true) ? 'checked' : '' }}>
            {{ __('admin.entities.f_active') }}
        </label>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const wilaya = document.getElementById('wilaya_id');
        const commune = document.getElementById('commune_id');
        const preselect = wilaya.getAttribute('data-selected-commune');

        async function loadCommunes(selected) {
            commune.innerHTML = '<option value="">{{ __('admin.entities.choose_commune') }}</option>';
            if (!wilaya.value) return;
            try {
                const res = await fetch(`/api/v1/wilayas/${wilaya.value}/communes`);
                const data = await res.json();
                (data.data ?? data).forEach(function (c) {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = (c.name_ar ?? c.name) + (c.postal_code ? ' (' + c.postal_code + ')' : '');
                    if (selected && String(selected) === String(c.id)) opt.selected = true;
                    commune.appendChild(opt);
                });
            } catch (e) { /* network error — leave commune empty */ }
        }

        wilaya.addEventListener('change', function () { loadCommunes(null); });
        if (wilaya.value) loadCommunes(preselect);
    });
</script>
@endpush
