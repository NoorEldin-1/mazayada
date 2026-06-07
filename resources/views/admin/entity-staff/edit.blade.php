@extends('layouts.admin')

@section('title', __('admin.entity_staff.edit_title'))
@section('page-title', __('admin.entity_staff.edit_title'))

@section('content')

<form method="POST" action="{{ route('admin.entity-staff.update', $member) }}">
    @csrf @method('PUT')

    <div class="card card-pad" style="margin-bottom:1.5rem">
        <h3 class="card-h">{{ $member->full_name ?? $member->user?->fullNameAr() }}</h3>

        <div class="field">
            <label>{{ __('admin.entity_staff.f_username') }}</label>
            <input type="text" class="input" value="{{ $member->username }}" style="direction:ltr" disabled>
        </div>

        <div class="field">
            <label>{{ __('admin.entity_staff.col_entity') }}</label>
            <input type="text" class="input" value="{{ $member->entity?->name }}" disabled>
        </div>

        <div class="field">
            <label for="role">{{ __('admin.entity_staff.f_role') }} <span style="color:var(--red-600)">*</span></label>
            <select id="role" name="role" class="select" required>
                @foreach($roles as $role)
                    <option value="{{ $role->value }}" {{ old('role', $member->role) === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                @endforeach
            </select>
            @error('role') <small style="color:var(--red-600)">{{ $message }}</small> @enderror
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">{{ __('admin.entity_staff.submit_update') }}</button>
</form>

@endsection
