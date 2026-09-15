@extends('layouts.admin')

@section('title', __('subscriptions.admin.create_plan'))
@section('page-title', __('subscriptions.admin.create_plan'))

@section('content')
<form method="POST" action="{{ route('admin.subscription-plans.store') }}">
    @csrf
    @include('admin.subscription-plans._form', ['plan' => null])
    <div class="flex gap-2">
        <x-ui.btn variant="primary">{{ __('common.save') }}</x-ui.btn>
        <x-ui.btn variant="ghost" :href="route('admin.subscription-plans.index')">{{ __('common.cancel') }}</x-ui.btn>
    </div>
</form>
@endsection
