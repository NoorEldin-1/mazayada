@extends('layouts.admin')

@section('title', __('admin.entity_staff.edit_title'))
@section('page-title', __('admin.entity_staff.edit_title'))

@section('content')

<form method="POST" action="{{ route('admin.entity-staff.update', $member) }}">
    @csrf @method('PUT')

    <x-ui.card :title="$member->full_name ?? $member->user?->fullNameAr()" class="mb-6">

        <div class="field">
            <label>{{ __('admin.entity_staff.f_username') }}</label>
            <input type="text" class="input" value="{{ $member->username }}" style="direction:ltr" disabled>
        </div>

        <div class="field">
            <label>{{ __('admin.entity_staff.col_entity') }}</label>
            <input type="text" class="input" value="{{ $member->entity?->name }}" disabled>
        </div>

        <div class="field">
            <label>{{ __('admin.entity_staff.f_professional_id') }}</label>
            <input type="text" class="input" value="{{ $member->user?->professional_id_no }}" style="direction:ltr" disabled>
        </div>

        <div class="field">
            <label for="role">{{ __('admin.entity_staff.f_role') }} <span class="text-danger">*</span></label>
            <select id="role" name="role" class="select" required>
                @foreach($roles as $role)
                    <option value="{{ $role->value }}" {{ old('role', $member->role) === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                @endforeach
            </select>
            @error('role') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
    </x-ui.card>

    <x-ui.btn variant="primary" size="lg">{{ __('admin.entity_staff.submit_update') }}</x-ui.btn>
</form>

@endsection
