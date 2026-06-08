@extends('layouts.admin')

@section('title', __('admin.entities.edit_title'))
@section('page-title', __('admin.entities.edit_title'))

@section('content')

<form method="POST" action="{{ route('admin.entities.update', $entity) }}">
    @csrf @method('PUT')
    @include('admin.entities._form', ['entity' => $entity])
    <x-ui.btn variant="primary" size="lg" class="w-full mt-6">{{ __('admin.entities.submit_update') }}</x-ui.btn>
</form>

@endsection
