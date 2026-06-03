@extends('layouts.app')
@section('title', __('legal.notices.title'))
@section('content')
    <x-legal.page base="legal.notices" current="notices" />
@endsection
