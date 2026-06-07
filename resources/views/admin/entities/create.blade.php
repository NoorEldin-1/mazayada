@extends('layouts.admin')

@section('title', __('admin.entities.add'))
@section('page-title', __('admin.entities.add'))

@section('content')

<form method="POST" action="{{ route('admin.entities.store') }}">
    @csrf
    @include('admin.entities._form', ['entity' => null])
    <button type="submit" class="btn btn-primary btn-block btn-lg">{{ __('admin.entities.submit_create') }}</button>
</form>

@endsection
