@extends('layouts.admin')

@section('title', __('subscriptions.admin.edit_plan'))
@section('page-title', __('subscriptions.admin.edit_plan'))

@section('content')
<form method="POST" action="{{ route('admin.subscription-plans.update', $plan) }}">
    @csrf
    @method('PUT')
    @include('admin.subscription-plans._form', ['plan' => $plan])
    <div class="flex gap-2">
        <x-ui.btn variant="primary">{{ __('common.save') }}</x-ui.btn>
        <x-ui.btn variant="ghost" :href="route('admin.subscription-plans.index')">{{ __('common.cancel') }}</x-ui.btn>
    </div>
</form>
@endsection
