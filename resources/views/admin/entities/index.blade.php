@extends('layouts.admin')

@section('title', __('admin.entities.title'))
@section('page-title', __('admin.entities.title'))

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <h3 class="font-semibold text-ink">{{ __('admin.entities.title') }}</h3>
    <x-ui.btn variant="primary" size="sm" :href="route('admin.entities.create')">{{ __('admin.entities.add') }}</x-ui.btn>
</div>

@if($errors->any())
    <div class="alert alert-danger" style="margin:1rem">{{ $errors->first() }}</div>
@endif

<x-ui.table>
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
            <tr>
                <td class="font-semibold text-ink">{{ $entity->name }}</td>
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
                    <x-ui.action-menu>
                        <x-ui.action-menu.item :href="route('admin.entities.show', $entity)">{{ __('common.view') }}</x-ui.action-menu.item>
                        <x-ui.action-menu.item :href="route('admin.entities.edit', $entity)">{{ __('common.edit') }}</x-ui.action-menu.item>
                        <x-ui.action-menu.item :action="route('admin.entities.destroy', $entity)" method="DELETE" variant="danger"
                            :confirm="__('admin.entities.confirm_delete')" confirm-variant="danger">{{ __('common.delete') }}</x-ui.action-menu.item>
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-8">{{ __('admin.entities.empty') }}</td></tr>
        @endforelse
    </tbody>
</x-ui.table>

<div class="mt-6">{{ $entities->links() }}</div>

@endsection
