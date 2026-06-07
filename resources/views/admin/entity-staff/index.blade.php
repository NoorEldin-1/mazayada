@extends('layouts.admin')

@section('title', __('admin.entity_staff.title'))
@section('page-title', __('admin.entity_staff.title'))

@section('content')

<div class="card">
    <div class="card-h">
        <h3>{{ __('admin.entity_staff.title') }}</h3>
        <div class="actions">
            <a href="{{ route('admin.entity-staff.create') }}" class="btn btn-primary btn-sm">{{ __('admin.entity_staff.add') }}</a>
        </div>
    </div>

    <table class="tbl">
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
                <tr class="row-hover">
                    <td style="font-weight:600">{{ $member->full_name ?? $member->user?->fullNameAr() }}</td>
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
                        <a href="{{ route('admin.entity-staff.edit', $member) }}" class="btn btn-ghost btn-sm">{{ __('common.edit') }}</a>
                        <form method="POST" action="{{ route('admin.entity-staff.toggle', $member) }}" style="display:inline"
                              data-confirm="{{ $member->is_active ? __('admin.entity_staff.confirm_deactivate') : __('admin.entity_staff.confirm_reactivate') }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:{{ $member->is_active ? 'var(--red-600)' : '#1d6045' }}">
                                {{ $member->is_active ? __('admin.entity_staff.deactivate') : __('admin.entity_staff.reactivate') }}
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--ink-muted)">{{ __('admin.entity_staff.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:1.5rem">{{ $staff->links() }}</div>

@endsection
