@extends('layouts.admin')

@section('title', __('subscriptions.admin.plans_title'))
@section('page-title', __('subscriptions.admin.plans_title'))

@section('content')

<div class="flex flex-wrap items-center justify-end gap-3 mb-5">
    <x-ui.btn variant="ghost" :href="route('admin.subscriptions.index')">{{ __('subscriptions.admin.subscriptions_title') }}</x-ui.btn>
    <x-ui.btn variant="primary" :href="route('admin.subscription-plans.create')">{{ __('subscriptions.admin.create_plan') }}</x-ui.btn>
</div>

<x-ui.table>
    <thead>
        <tr>
            <th>{{ __('subscriptions.admin.f_code') }}</th>
            <th>{{ __('subscriptions.admin.f_name_ar') }}</th>
            <th>{{ __('subscriptions.admin.f_period') }}</th>
            <th>{{ __('subscriptions.admin.f_price') }}</th>
            <th>{{ __('subscriptions.admin.th_subscribers') }}</th>
            <th>{{ __('admin.th_status') }}</th>
            <th>{{ __('common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($plans as $plan)
            <tr>
                <td class="num" dir="ltr">{{ $plan->code }}</td>
                <td>
                    {{ $plan->name }}
                    @if($plan->is_recommended)<span class="chip chip-info ms-1">{{ __('subscriptions.recommended') }}</span>@endif
                </td>
                <td>{{ $plan->period?->label() }}</td>
                <td class="num"><x-money :centimes="$plan->price" /></td>
                <td class="num">{{ $plan->subscriptions_count }}</td>
                <td><span class="chip {{ $plan->is_active ? 'chip-ok' : 'chip-muted' }}">{{ $plan->is_active ? __('subscriptions.admin.f_active') : __('subscriptions.admin.deactivate') }}</span></td>
                <td>
                    <x-ui.action-menu>
                        <x-ui.action-menu.item :href="route('admin.subscription-plans.edit', $plan)">{{ __('common.edit') }}</x-ui.action-menu.item>
                        <x-ui.action-menu.item :action="route('admin.subscription-plans.toggle', $plan)" :variant="$plan->is_active ? 'danger' : null">
                            {{ $plan->is_active ? __('subscriptions.admin.deactivate') : __('subscriptions.admin.activate') }}
                        </x-ui.action-menu.item>
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-8">{{ __('subscriptions.admin.no_plans') }}</td></tr>
        @endforelse
    </tbody>
</x-ui.table>

@endsection
