@extends('layouts.admin')

@section('title', __('publication.create'))
@section('page-title', __('publication.create'))

@section('content')
<form method="POST" action="{{ route('admin.publication-packages.store') }}">
    @csrf
    @include('admin.publication-packages._form', ['package' => null])
    <div class="flex gap-2">
        <x-ui.btn variant="primary">{{ __('common.save') }}</x-ui.btn>
        <x-ui.btn variant="ghost" :href="route('admin.publication-packages.index')">{{ __('common.cancel') }}</x-ui.btn>
    </div>
</form>
@endsection
