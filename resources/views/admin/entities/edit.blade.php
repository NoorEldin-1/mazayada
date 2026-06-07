@extends('layouts.admin')

@section('title', __('admin.entities.edit_title'))
@section('page-title', __('admin.entities.edit_title'))

@section('content')

<form method="POST" action="{{ route('admin.entities.update', $entity) }}">
    @csrf @method('PUT')
    @include('admin.entities._form', ['entity' => $entity])
    <button type="submit" class="btn btn-primary btn-block btn-lg">{{ __('admin.entities.submit_update') }}</button>
</form>

@endsection
