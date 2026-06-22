@extends('layouts.admin')

@section('title', __('admin.entity_staff.add'))
@section('page-title', __('admin.entity_staff.add'))

@section('content')

<form method="POST" action="{{ route('admin.entity-staff.store') }}">
    @csrf

    <x-ui.card :title="__('admin.entity_staff.sec_assignment')" class="mb-6">

        @if($entities !== null)
            <div class="field">
                <label for="entity_id">{{ __('admin.entity_staff.f_entity') }} <span class="text-danger">*</span></label>
                <select id="entity_id" name="entity_id" class="select" required>
                    <option value="">{{ __('admin.entity_staff.choose_entity') }}</option>
                    @foreach($entities as $entity)
                        <option value="{{ $entity->id }}" {{ old('entity_id') == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                    @endforeach
                </select>
                @error('entity_id') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
        @else
            <p class="text-sm text-muted mb-4">{{ __('admin.entity_staff.own_entity_note') }}</p>
        @endif

        {{-- Every staff account is a read-only entity viewer — no role to choose. --}}
        <p class="text-sm text-muted">{{ __('admin.entity_staff.read_only_note') }}</p>
    </x-ui.card>

    <x-ui.card :title="__('admin.entity_staff.sec_identity')" class="mb-6">

        <div class="field">
            <label for="nin">{{ __('admin.entity_staff.f_nin') }} <span class="text-danger">*</span></label>
            <input type="text" id="nin" name="nin" class="input" value="{{ old('nin') }}" maxlength="18" style="direction:ltr" required>
            @error('nin') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="professional_id_no">{{ __('admin.entity_staff.f_professional_id') }} <span class="text-danger">*</span></label>
            <input type="text" id="professional_id_no" name="professional_id_no" class="input" value="{{ old('professional_id_no') }}" maxlength="20" style="direction:ltr" required>
            @error('professional_id_no') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="first_name_ar">{{ __('admin.entity_staff.f_first_name_ar') }} <span class="text-danger">*</span></label>
                <input type="text" id="first_name_ar" name="first_name_ar" class="input" value="{{ old('first_name_ar') }}" required>
                @error('first_name_ar') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="last_name_ar">{{ __('admin.entity_staff.f_last_name_ar') }} <span class="text-danger">*</span></label>
                <input type="text" id="last_name_ar" name="last_name_ar" class="input" value="{{ old('last_name_ar') }}" required>
                @error('last_name_ar') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="phone">{{ __('admin.entity_staff.f_phone') }} <span class="text-danger">*</span></label>
                <input type="text" id="phone" name="phone" class="input" value="{{ old('phone') }}" style="direction:ltr" required>
                @error('phone') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="email">{{ __('admin.entity_staff.f_email') }} <span class="text-danger">*</span></label>
                <input type="email" id="email" name="email" class="input" value="{{ old('email') }}" style="direction:ltr" required>
                @error('email') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="birth_date">{{ __('admin.entity_staff.f_birth_date') }} <span class="text-danger">*</span></label>
                <input type="date" id="birth_date" name="birth_date" class="input" value="{{ old('birth_date') }}" required>
                @error('birth_date') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="username">{{ __('admin.entity_staff.f_username') }} <span class="text-danger">*</span></label>
                <input type="text" id="username" name="username" class="input" value="{{ old('username') }}" style="direction:ltr" required>
                @error('username') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
        </div>
    </x-ui.card>

    <x-ui.card :title="__('admin.entity_staff.sec_credentials')" class="mb-6">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="password">{{ __('admin.entity_staff.f_password') }} <span class="text-danger">*</span></label>
                <input type="password" id="password" name="password" class="input" style="direction:ltr" required>
                @error('password') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="password_confirmation">{{ __('admin.entity_staff.f_password_confirm') }} <span class="text-danger">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="input" style="direction:ltr" required>
            </div>
        </div>
    </x-ui.card>

    <x-ui.btn variant="primary" size="lg" class="w-full">{{ __('admin.entity_staff.submit_create') }}</x-ui.btn>
</form>

@endsection
