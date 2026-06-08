@extends('layouts.admin')

@section('title', __('admin.settings.title'))
@section('page-title', __('admin.settings.title'))

@section('content')

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf @method('PUT')

    <p class="text-sm text-muted mb-4">{{ __('admin.settings.intro') }}</p>

    @foreach($settings as $group => $rows)
        <x-ui.card :title="__('admin.settings.group_'.$group)" class="mb-6">

            @foreach($rows as $setting)
                {{-- Label + technical key grouped ABOVE the input so each field
                     reads as one clear unit (the key never floats next to the
                     next field's label). --}}
                <div class="field" style="margin-bottom:18px;{{ ! $loop->last ? 'padding-bottom:16px;border-bottom:1px solid var(--line);' : '' }}">
                    <label for="set-{{ $setting->key }}" style="display:block;font-weight:600;margin-bottom:2px">
                        {{ __('admin.settings.key_'.str_replace('.', '_', $setting->key)) }}
                    </label>
                    <code style="display:block;font-size:11px;color:var(--muted);direction:ltr;text-align:start;margin-bottom:8px">{{ $setting->key }}</code>

                    @if($setting->type === 'bool')
                        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                            <input type="checkbox" id="set-{{ $setting->key }}" name="settings[{{ $setting->key }}]" value="1" {{ $setting->typedValue() ? 'checked' : '' }}>
                            {{ __('admin.settings.enabled') }}
                        </label>
                    @else
                        <input type="{{ in_array($setting->type, ['int','float']) ? 'number' : 'text' }}"
                               id="set-{{ $setting->key }}"
                               name="settings[{{ $setting->key }}]"
                               class="input num"
                               value="{{ $setting->value }}"
                               @if($setting->type === 'float') step="0.01" @endif
                               style="max-width:240px">
                    @endif
                </div>
            @endforeach
        </x-ui.card>
    @endforeach

    <x-ui.btn variant="primary" size="lg">{{ __('admin.settings.submit') }}</x-ui.btn>
</form>

@endsection
