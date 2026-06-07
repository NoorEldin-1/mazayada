@extends('layouts.admin')

@section('title', __('admin.entity_staff.add'))
@section('page-title', __('admin.entity_staff.add'))

@section('content')

<form method="POST" action="{{ route('admin.entity-staff.store') }}">
    @csrf

    <div class="card card-pad" style="margin-bottom:1.5rem">
        <h3 class="card-h">{{ __('admin.entity_staff.sec_assignment') }}</h3>

        @if($entities !== null)
            <div class="field">
                <label for="entity_id">{{ __('admin.entity_staff.f_entity') }} <span style="color:var(--red-600)">*</span></label>
                <select id="entity_id" name="entity_id" class="select" required>
                    <option value="">{{ __('admin.entity_staff.choose_entity') }}</option>
                    @foreach($entities as $entity)
                        <option value="{{ $entity->id }}" {{ old('entity_id') == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                    @endforeach
                </select>
                @error('entity_id') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>
        @else
            <p style="font-size:0.9rem;color:var(--ink-muted)">{{ __('admin.entity_staff.own_entity_note') }}</p>
        @endif

        <div class="field">
            <label for="role">{{ __('admin.entity_staff.f_role') }} <span style="color:var(--red-600)">*</span></label>
            <select id="role" name="role" class="select" required>
                <option value="">{{ __('admin.entity_staff.choose_role') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->value }}" {{ old('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                @endforeach
            </select>
            @error('role') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="card card-pad" style="margin-bottom:1.5rem">
        <h3 class="card-h">{{ __('admin.entity_staff.sec_identity') }}</h3>

        <div class="field">
            <label for="nin">{{ __('admin.entity_staff.f_nin') }} <span style="color:var(--red-600)">*</span></label>
            <input type="text" id="nin" name="nin" class="input" value="{{ old('nin') }}" maxlength="18" style="direction:ltr" required>
            @error('nin') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="first_name_ar">{{ __('admin.entity_staff.f_first_name_ar') }} <span style="color:var(--red-600)">*</span></label>
                <input type="text" id="first_name_ar" name="first_name_ar" class="input" value="{{ old('first_name_ar') }}" required>
                @error('first_name_ar') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="last_name_ar">{{ __('admin.entity_staff.f_last_name_ar') }} <span style="color:var(--red-600)">*</span></label>
                <input type="text" id="last_name_ar" name="last_name_ar" class="input" value="{{ old('last_name_ar') }}" required>
                @error('last_name_ar') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="phone">{{ __('admin.entity_staff.f_phone') }} <span style="color:var(--red-600)">*</span></label>
                <input type="text" id="phone" name="phone" class="input" value="{{ old('phone') }}" style="direction:ltr" required>
                @error('phone') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="email">{{ __('admin.entity_staff.f_email') }} <span style="color:var(--red-600)">*</span></label>
                <input type="email" id="email" name="email" class="input" value="{{ old('email') }}" style="direction:ltr" required>
                @error('email') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="birth_date">{{ __('admin.entity_staff.f_birth_date') }} <span style="color:var(--red-600)">*</span></label>
                <input type="date" id="birth_date" name="birth_date" class="input" value="{{ old('birth_date') }}" required>
                @error('birth_date') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="username">{{ __('admin.entity_staff.f_username') }} <span style="color:var(--red-600)">*</span></label>
                <input type="text" id="username" name="username" class="input" value="{{ old('username') }}" style="direction:ltr" required>
                @error('username') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>
        </div>
    </div>

    <div class="card card-pad" style="margin-bottom:1.5rem">
        <h3 class="card-h">{{ __('admin.entity_staff.sec_credentials') }}</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="password">{{ __('admin.entity_staff.f_password') }} <span style="color:var(--red-600)">*</span></label>
                <input type="password" id="password" name="password" class="input" style="direction:ltr" required>
                @error('password') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="password_confirmation">{{ __('admin.entity_staff.f_password_confirm') }} <span style="color:var(--red-600)">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="input" style="direction:ltr" required>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg">{{ __('admin.entity_staff.submit_create') }}</button>
</form>

@endsection
