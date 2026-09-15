@extends('layouts.admin')

@section('title', __('subscriptions.admin.subscriptions_title'))
@section('page-title', __('subscriptions.admin.subscriptions_title'))

@section('content')

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
    <x-ui.card><div class="text-xs text-muted">{{ __('subscriptions.admin.stat_active') }}</div><div class="text-2xl font-bold num">{{ $stats['active'] }}</div></x-ui.card>
    <x-ui.card><div class="text-xs text-muted">{{ __('subscriptions.admin.stat_pending') }}</div><div class="text-2xl font-bold num">{{ $stats['pending'] }}</div></x-ui.card>
    <x-ui.card><div class="text-xs text-muted">{{ __('subscriptions.admin.stat_auto_renew_off') }}</div><div class="text-2xl font-bold num">{{ $stats['auto_renew_off'] }}</div></x-ui.card>
</div>

<form method="GET" class="flex flex-wrap items-end gap-3 mb-5">
    <input type="text" name="search" class="input" style="max-width:260px" value="{{ request('search') }}" placeholder="{{ __('subscriptions.admin.search_placeholder') }}">
    <select name="status" class="select" style="max-width:200px">
        <option value="">{{ __('subscriptions.admin.filter_all_statuses') }}</option>
        @foreach(\App\Enums\SubscriptionStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <select name="plan" class="select" style="max-width:220px">
        <option value="">{{ __('subscriptions.admin.filter_all_plans') }}</option>
        @foreach($plans as $plan)
            <option value="{{ $plan->id }}" @selected((string) request('plan') === (string) $plan->id)>{{ $plan->name }}</option>
        @endforeach
    </select>
    <x-ui.btn variant="outline">{{ __('subscriptions.admin.filter') }}</x-ui.btn>
    <x-ui.btn variant="ghost" :href="route('admin.subscription-plans.index')" class="ms-auto">{{ __('subscriptions.admin.plans_title') }}</x-ui.btn>
</form>

<x-ui.table>
    <thead>
        <tr>
            <th>{{ __('subscriptions.admin.th_user') }}</th>
            <th>{{ __('subscriptions.plan') }}</th>
            <th>{{ __('subscriptions.status') }}</th>
            <th>{{ __('subscriptions.started_at') }}</th>
            <th>{{ __('subscriptions.expires_at') }}</th>
            <th>{{ __('subscriptions.auto_renew') }}</th>
            <th>{{ __('subscriptions.admin.f_price') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($subscriptions as $s)
            <tr>
                <td>
                    <div>{{ $s->user?->fullNameAr() }}</div>
                    <div class="text-xs text-muted" dir="ltr">{{ $s->user?->email }}</div>
                </td>
                <td>{{ $s->plan?->name }}</td>
                <td><span class="chip {{ $s->status->chipClass() }}">{{ $s->status->label() }}</span></td>
                <td class="num">{{ $s->started_at?->format('Y-m-d') ?? '—' }}</td>
                <td class="num">{{ $s->expires_at?->format('Y-m-d') ?? '—' }}</td>
                <td>{{ $s->auto_renew ? __('subscriptions.auto_renew_on') : __('subscriptions.auto_renew_off') }}</td>
                <td class="num"><x-money :centimes="$s->price" /></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-8">{{ __('subscriptions.admin.no_subscriptions') }}</td></tr>
        @endforelse
    </tbody>
</x-ui.table>

<div class="mt-6">{{ $subscriptions->links() }}</div>

@endsection
