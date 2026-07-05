@extends('layouts.citizen')
@section('title', __('reports.title_personal'))

@section('content')
@php
    $routeIndex = 'citizen.reports';
@endphp

<div class="flex flex-wrap items-start gap-3 mb-5">
    <div class="min-w-0">
        <h1 class="text-xl font-bold text-ink">{{ __('reports.title_personal') }}</h1>
        <p class="text-sm text-muted mt-0.5">{{ __('reports.subtitle_personal') }}</p>
        <p class="text-xs text-muted mt-1">{{ __('reports.pdf_range') }}: <span class="font-medium text-ink">{{ $filters->rangeLabel() }}</span></p>
    </div>
    <div class="flex items-center gap-2 ms-auto">
        <x-ui.btn :href="route('citizen.reports.export.csv', $filters->toQuery())" variant="outline" size="sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            {{ __('reports.export_csv') }}
        </x-ui.btn>
        <x-ui.btn :href="route('citizen.reports.export.pdf', $filters->toQuery())" variant="outline" size="sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            {{ __('reports.export_pdf') }}
        </x-ui.btn>
    </div>
</div>

@include('reports._filters')
@include('reports._summary')
@include('reports._charts')
@include('reports._transactions')
@endsection
