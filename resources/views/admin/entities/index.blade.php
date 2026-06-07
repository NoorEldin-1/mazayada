@extends('layouts.admin')

@section('title', __('admin.entities.title'))
@section('page-title', __('admin.entities.title'))

@section('content')

<div class="card">
    <div class="card-h">
        <h3>{{ __('admin.entities.title') }}</h3>
        <div class="actions">
            <a href="{{ route('admin.entities.create') }}" class="btn btn-primary btn-sm">{{ __('admin.entities.add') }}</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" style="margin:1rem">{{ $errors->first() }}</div>
    @endif

    <table class="tbl">
        <thead>
            <tr>
                <th>{{ __('admin.entities.f_name') }}</th>
                <th>{{ __('admin.entities.f_type') }}</th>
                <th>{{ __('admin.entities.f_wilaya') }}</th>
                <th>{{ __('admin.entities.col_staff') }}</th>
                <th>{{ __('admin.entities.col_auctions') }}</th>
                <th>{{ __('admin.entities.f_active') }}</th>
                <th>{{ __('common.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entities as $entity)
                <tr class="row-hover">
                    <td style="font-weight:600">{{ $entity->name }}</td>
                    <td><span class="chip chip-info">{{ $entity->type->label() }}</span></td>
                    <td>{{ $entity->wilaya?->name ?? '—' }}</td>
                    <td class="num">{{ $entity->entity_users_count }}</td>
                    <td class="num">{{ $entity->auctions_count }}</td>
                    <td>
                        @if($entity->is_active)
                            <span class="chip chip-ok">{{ __('common.active') }}</span>
                        @else
                            <span class="chip chip-muted">{{ __('common.inactive') }}</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.entities.edit', $entity) }}" class="btn btn-ghost btn-sm">{{ __('common.edit') }}</a>
                        <form method="POST" action="{{ route('admin.entities.destroy', $entity) }}" style="display:inline"
                              data-confirm="{{ __('admin.entities.confirm_delete') }}" data-confirm-variant="danger">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red-600)">{{ __('common.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--ink-muted)">{{ __('admin.entities.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:1.5rem">{{ $entities->links() }}</div>

@endsection
