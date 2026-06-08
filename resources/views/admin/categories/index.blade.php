@extends('layouts.admin')

@section('title', __('admin.categories.title'))
@section('page-title', __('admin.categories.title'))

@section('content')

<x-ui.page-header :title="__('admin.categories.title')">
    <x-slot:actions>
        <x-ui.btn variant="primary" size="sm" :href="route('admin.categories.create')">{{ __('admin.categories.add') }}</x-ui.btn>
    </x-slot:actions>
</x-ui.page-header>

@if($errors->any())
    <div class="mb-4 rounded-xl bg-danger/10 text-danger px-4 py-3 text-sm">{{ $errors->first() }}</div>
@endif

<x-ui.table>
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
            <tr>
                <td class="font-semibold text-ink">{{ $category->name_ar }}</td>
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
                    <x-ui.btn variant="ghost" size="sm" :href="route('admin.categories.edit', $category)">{{ __('common.edit') }}</x-ui.btn>
                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" style="display:inline"
                          data-confirm="{{ __('admin.categories.confirm_delete') }}" data-confirm-variant="danger">
                        @csrf @method('DELETE')
                        <x-ui.btn variant="danger-ghost" size="sm">{{ __('common.delete') }}</x-ui.btn>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-8">{{ __('admin.categories.empty') }}</td></tr>
        @endforelse
    </tbody>
</x-ui.table>

@endsection
