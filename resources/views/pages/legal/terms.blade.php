@extends('layouts.app')
@section('title', __('legal.terms.title'))
@section('content')
    <x-legal.page base="legal.terms" current="terms" />
@endsection
