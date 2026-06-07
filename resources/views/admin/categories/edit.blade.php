@extends('layouts.admin')

@section('title', __('admin.categories.edit_title'))
@section('page-title', __('admin.categories.edit_title'))

@section('content')

<form method="POST" action="{{ route('admin.categories.update', $category) }}">
    @csrf @method('PUT')
    @include('admin.categories._form', ['category' => $category])
    <button type="submit" class="btn btn-primary btn-block btn-lg">{{ __('admin.categories.submit_update') }}</button>
</form>

@endsection
