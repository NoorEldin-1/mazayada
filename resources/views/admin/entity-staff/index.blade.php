@extends('layouts.admin')

@section('title', __('admin.entity_staff.title'))
@section('page-title', __('admin.entity_staff.title'))

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <h3 class="font-semibold text-ink">{{ __('admin.entity_staff.title') }}</h3>
    <x-ui.btn variant="primary" size="sm" :href="route('admin.entity-staff.create')">{{ __('admin.entity_staff.add') }}</x-ui.btn>
</div>

<x-ui.table>
    <thead>
        <tr>
            <th>{{ __('admin.entity_staff.f_full_name') }}</th>
            <th>{{ __('admin.entity_staff.f_username') }}</th>
            <th>{{ __('admin.entity_staff.col_entity') }}</th>
            <th>{{ __('admin.entity_staff.f_role') }}</th>
            <th>{{ __('admin.entity_staff.col_status') }}</th>
            <th>{{ __('common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($staff as $member)
            <tr>
                <td class="font-semibold text-ink">{{ $member->full_name ?? $member->user?->fullNameAr() }}</td>
                <td style="direction:ltr;text-align:start">{{ $member->username }}</td>
                <td>{{ $member->entity?->name ?? '—' }}</td>
                <td><span class="chip chip-info">{{ \App\Enums\UserRole::tryFrom($member->role)?->label() ?? $member->role }}</span></td>
                <td>
                    @if($member->is_active)
                        <span class="chip chip-ok">{{ __('common.active') }}</span>
                    @else
                        <span class="chip chip-muted">{{ __('common.inactive') }}</span>
                    @endif
                </td>
                <td>
                    <x-ui.btn variant="ghost" size="sm" :href="route('admin.entity-staff.edit', $member)">{{ __('common.edit') }}</x-ui.btn>
                    <form method="POST" action="{{ route('admin.entity-staff.toggle', $member) }}" style="display:inline"
                          data-confirm="{{ $member->is_active ? __('admin.entity_staff.confirm_deactivate') : __('admin.entity_staff.confirm_reactivate') }}">
                        @csrf
                        <x-ui.btn :variant="$member->is_active ? 'danger-ghost' : 'ghost'" size="sm">
                            {{ $member->is_active ? __('admin.entity_staff.deactivate') : __('admin.entity_staff.reactivate') }}
                        </x-ui.btn>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-8">{{ __('admin.entity_staff.empty') }}</td></tr>
        @endforelse
    </tbody>
</x-ui.table>

<div class="mt-6">{{ $staff->links() }}</div>

@endsection
