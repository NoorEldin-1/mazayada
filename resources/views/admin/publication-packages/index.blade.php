@extends('layouts.admin')

@section('title', __('publication.title'))
@section('page-title', __('publication.title'))

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <p class="text-sm text-muted max-w-3xl">{{ __('publication.intro') }}</p>
    <x-ui.btn variant="primary" :href="route('admin.publication-packages.create')">{{ __('publication.create') }}</x-ui.btn>
</div>

<x-ui.table>
    <thead>
        <tr>
            <th>{{ __('publication.f_code') }}</th>
            <th>{{ __('publication.f_name_ar') }}</th>
            <th>{{ __('publication.f_display_area') }}</th>
            <th>{{ __('publication.f_price') }}</th>
            <th>{{ __('publication.f_priority_price') }}</th>
            <th>{{ __('publication.f_duration_days') }}</th>
            <th>{{ __('publication.th_auctions') }}</th>
            <th>{{ __('admin.th_status') }}</th>
            <th>{{ __('common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($packages as $package)
            <tr>
                <td class="num" dir="ltr">{{ $package->code }}</td>
                <td>{{ $package->name }}</td>
                <td>{{ $package->display_area?->label() }}</td>
                <td class="num"><x-money :centimes="$package->price" /></td>
                <td class="num"><x-money :centimes="$package->priority_price" /></td>
                <td class="num">{{ $package->duration_days }}</td>
                <td class="num">{{ $package->auctions_count }}</td>
                <td>
                    <span class="chip {{ $package->is_active ? 'chip-ok' : 'chip-muted' }}">{{ $package->is_active ? __('publication.f_active') : __('publication.deactivate') }}</span>
                </td>
                <td>
                    <x-ui.action-menu>
                        <x-ui.action-menu.item :href="route('admin.publication-packages.edit', $package)">{{ __('common.edit') }}</x-ui.action-menu.item>
                        <x-ui.action-menu.item :action="route('admin.publication-packages.toggle', $package)" :variant="$package->is_active ? 'danger' : null">
                            {{ $package->is_active ? __('publication.deactivate') : __('publication.activate') }}
                        </x-ui.action-menu.item>
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center text-muted py-8">{{ __('publication.none') }}</td></tr>
        @endforelse
    </tbody>
</x-ui.table>

@endsection
