@extends('layouts.admin')

@section('title', __('admin.categories.title'))
@section('page-title', __('admin.categories.title'))

@section('content')

<div class="card">
    <div class="card-h">
        <h3>{{ __('admin.categories.title') }}</h3>
        <div class="actions">
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">{{ __('admin.categories.add') }}</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" style="margin:1rem">{{ $errors->first() }}</div>
    @endif

    <table class="tbl">
        <thead>
            <tr>
                <th>{{ __('admin.categories.f_name_ar') }}</th>
                <th>{{ __('admin.categories.f_name_fr') }}</th>
                <th>{{ __('admin.categories.col_auctions') }}</th>
                <th>{{ __('admin.categories.f_active') }}</th>
                <th>{{ __('common.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $category)
                <tr class="row-hover">
                    <td style="font-weight:600">{{ $category->name_ar }}</td>
                    <td>{{ $category->name_fr ?? '—' }}</td>
                    <td class="num">{{ $category->auctions_count }}</td>
                    <td>
                        @if($category->is_active)
                            <span class="chip chip-ok">{{ __('common.active') }}</span>
                        @else
                            <span class="chip chip-muted">{{ __('common.inactive') }}</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-ghost btn-sm">{{ __('common.edit') }}</a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" style="display:inline"
                              data-confirm="{{ __('admin.categories.confirm_delete') }}" data-confirm-variant="danger">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red-600)">{{ __('common.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--ink-muted)">{{ __('admin.categories.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
