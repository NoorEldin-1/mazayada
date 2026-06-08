@extends('layouts.admin')

@section('title', __('admin.categories.add'))
@section('page-title', __('admin.categories.add'))

@section('content')

<form method="POST" action="{{ route('admin.categories.store') }}">
    @csrf
    @include('admin.categories._form', ['category' => null])
    <x-ui.btn variant="primary" size="lg" class="w-full mt-6">{{ __('admin.categories.submit_create') }}</x-ui.btn>
</form>

@endsection
