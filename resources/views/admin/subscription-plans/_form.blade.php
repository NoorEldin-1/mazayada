{{-- Shared Premium plan create/edit form. $plan is null on create. --}}
@php
    $p = $plan;
    $features = (array) ($p?->features ?? []);
@endphp

<x-ui.card class="mb-6">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:1rem">
        <div class="field">
            <label for="code">{{ __('subscriptions.admin.f_code') }} <span class="text-danger">*</span></label>
            <input type="text" id="code" name="code" class="input" dir="ltr" value="{{ old('code', $p?->code) }}" required maxlength="40">
            @error('code') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="period">{{ __('subscriptions.admin.f_period') }} <span class="text-danger">*</span></label>
            <select id="period" name="period" class="select" required>
                @foreach(\App\Enums\SubscriptionPeriod::cases() as $period)
                    <option value="{{ $period->value }}" @selected(old('period', $p?->period?->value) === $period->value)>{{ $period->label() }}</option>
                @endforeach
            </select>
            @error('period') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="price">{{ __('subscriptions.admin.f_price') }} <span class="text-danger">*</span></label>
            <input type="number" id="price" name="price" class="input num" min="50" step="0.01" value="{{ old('price', $p ? $p->price / 100 : '') }}" required>
            @error('price') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="sort_order">{{ __('subscriptions.admin.f_sort') }}</label>
            <input type="number" id="sort_order" name="sort_order" class="input num" min="0" value="{{ old('sort_order', $p?->sort_order ?? 0) }}">
        </div>
        @foreach(['ar', 'fr', 'en'] as $loc)
            <div class="field">
                <label for="name_{{ $loc }}">{{ __('subscriptions.admin.f_name_'.$loc) }} @if($loc === 'ar')<span class="text-danger">*</span>@endif</label>
                <input type="text" id="name_{{ $loc }}" name="name_{{ $loc }}" class="input" @if($loc !== 'ar') dir="ltr" @endif value="{{ old('name_'.$loc, $p?->{'name_'.$loc}) }}" @if($loc === 'ar') required @endif>
                @error('name_'.$loc) <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:1rem;margin-top:1rem">
        @foreach(['ar', 'fr', 'en'] as $loc)
            <div class="field">
                <label for="description_{{ $loc }}">{{ __('subscriptions.admin.f_description_'.$loc) }}</label>
                <textarea id="description_{{ $loc }}" name="description_{{ $loc }}" class="input" rows="2" @if($loc !== 'ar') dir="ltr" @endif>{{ old('description_'.$loc, $p?->{'description_'.$loc}) }}</textarea>
            </div>
        @endforeach
        @foreach(['ar', 'fr', 'en'] as $loc)
            <div class="field">
                <label for="features_{{ $loc }}">{{ __('subscriptions.admin.f_features_'.$loc) }}</label>
                <textarea id="features_{{ $loc }}" name="features_{{ $loc }}" class="input" rows="4" @if($loc !== 'ar') dir="ltr" @endif>{{ old('features_'.$loc, implode("\n", (array) ($features[$loc] ?? []))) }}</textarea>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-5" style="margin-top:0.75rem">
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
            <input type="checkbox" name="is_recommended" value="1" {{ old('is_recommended', $p?->is_recommended) ? 'checked' : '' }}>
            {{ __('subscriptions.admin.f_recommended') }}
        </label>
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $p?->is_active ?? true) ? 'checked' : '' }}>
            {{ __('subscriptions.admin.f_active') }}
        </label>
    </div>
</x-ui.card>
