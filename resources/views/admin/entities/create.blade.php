@extends('layouts.admin')

@section('title', __('admin.entities.add'))
@section('page-title', __('admin.entities.add'))

@section('content')

<form method="POST" action="{{ route('admin.entities.store') }}">
    @csrf
    @include('admin.entities._form', ['entity' => null])
    <x-ui.btn variant="primary" size="lg" class="w-full mt-6">{{ __('admin.entities.submit_create') }}</x-ui.btn>
</form>

@endsection
