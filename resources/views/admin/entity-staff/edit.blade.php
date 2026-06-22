@extends('layouts.admin')

@section('title', __('admin.entity_staff.reset_password_title'))
@section('page-title', __('admin.entity_staff.reset_password_title'))

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
            <label>{{ __('admin.entity_staff.f_role') }}</label>
            <input type="text" class="input" value="{{ \App\Enums\UserRole::tryFrom($member->role)?->label() ?? $member->role }}" disabled>
        </div>
    </x-ui.card>

    {{-- Identity and role are fixed at creation; the admin can only rotate the
         login password (the entity cannot manage its own staff). --}}
    <x-ui.card :title="__('admin.entity_staff.sec_reset_password')" class="mb-6">
        <p class="text-sm text-muted mb-4">{{ __('admin.entity_staff.reset_password_note') }}</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="field">
                <label for="password">{{ __('admin.entity_staff.f_password') }} <span class="text-danger">*</span></label>
                <input type="password" id="password" name="password" class="input" style="direction:ltr" autocomplete="new-password" required>
                @error('password') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="password_confirmation">{{ __('admin.entity_staff.f_password_confirm') }} <span class="text-danger">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="input" style="direction:ltr" autocomplete="new-password" required>
            </div>
        </div>
    </x-ui.card>

    <x-ui.btn variant="primary" size="lg">{{ __('admin.entity_staff.submit_reset_password') }}</x-ui.btn>
</form>

@endsection
