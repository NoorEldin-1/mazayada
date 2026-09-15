@extends('layouts.admin')

@section('title', __('publication.edit'))
@section('page-title', __('publication.edit'))

@section('content')
<form method="POST" action="{{ route('admin.publication-packages.update', $package) }}">
    @csrf
    @method('PUT')
    @include('admin.publication-packages._form', ['package' => $package])
    <div class="flex gap-2">
        <x-ui.btn variant="primary">{{ __('common.save') }}</x-ui.btn>
        <x-ui.btn variant="ghost" :href="route('admin.publication-packages.index')">{{ __('common.cancel') }}</x-ui.btn>
    </div>
</form>
@endsection
