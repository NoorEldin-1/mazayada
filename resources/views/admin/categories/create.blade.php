@extends('layouts.admin')

@section('title', __('admin.categories.add'))
@section('page-title', __('admin.categories.add'))

@section('content')

<form method="POST" action="{{ route('admin.categories.store') }}">
    @csrf
    @include('admin.categories._form', ['category' => null])
    <button type="submit" class="btn btn-primary btn-block btn-lg">{{ __('admin.categories.submit_create') }}</button>
</form>

@endsection
