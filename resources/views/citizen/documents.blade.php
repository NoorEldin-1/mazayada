@extends('layouts.citizen')
@section('title', __('documents.lib_title'))
@section('content')

@php
    use App\Enums\DocumentType;
    // Icon-box tone per document type — mirrors the stat-tile palette.
    $iconTone = [
        DocumentType::CONDITION_BOOK->value => 'bg-info/12 text-info',
        DocumentType::AWARD->value          => 'bg-accent/15 text-accent-2',
        DocumentType::PAYMENT_RECEIPT->value => 'bg-ok/12 text-ok',
        DocumentType::DELIVERY_REPORT->value => 'bg-primary/10 text-primary',
    ];
@endphp

<x-ui.page-header :title="__('documents.lib_title')" :subtitle="__('documents.lib_subtitle')" />

{{-- Stat tiles --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <x-ui.stat-tile layout="stacked" tone="mint" :label="__('documents.lib_stat_total')" :value="$stats['total']"
        :hint="__('documents.lib_stat_size_hint', ['size' => human_filesize($stats['total_bytes'])])">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile layout="stacked" tone="blue" :label="__('documents.lib_stat_books')" :value="$stats['books']">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile layout="stacked" tone="gold" :label="__('documents.lib_stat_awards')" :value="$stats['awards']">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile layout="stacked" tone="ok" :label="__('documents.lib_stat_receipts')" :value="$stats['receipts']">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8M8 11h8M8 15h5"/></svg>
    </x-ui.stat-tile>
</div>

@include('citizen.documents._filters')

{{-- View toggle --}}
<div class="flex items-center justify-between gap-3 mb-4">
    <div class="text-sm text-muted">
        {{ __('documents.lib_docs_count', ['count' => $documents->total()]) }}
    </div>
    <div class="inline-flex rounded-lg border border-line bg-bg p-0.5">
        @foreach(['grouped' => 'lib_view_grouped', 'flat' => 'lib_view_flat'] as $key => $label)
            <a href="{{ route('citizen.documents', array_merge($filters->toQuery(), ['view' => $key])) }}"
               class="px-3 py-1.5 rounded-md text-sm font-medium transition {{ $view === $key ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink' }}">
                {{ __('documents.'.$label) }}
            </a>
        @endforeach
    </div>
</div>

@if($documents->isEmpty())
    <x-ui.card>
        <div class="text-center py-14">
            <svg class="mx-auto mb-4 text-muted/40" width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.25" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            @if($filters->isActive())
                <h3 class="text-base font-semibold text-ink">{{ __('documents.lib_empty_filtered_title') }}</h3>
                <p class="text-sm text-muted mt-1">{{ __('documents.lib_empty_filtered_body') }}</p>
                <x-ui.btn :href="route('citizen.documents')" variant="outline" size="sm" class="mt-4">{{ __('documents.lib_reset') }}</x-ui.btn>
            @else
                <h3 class="text-base font-semibold text-ink">{{ __('documents.lib_empty_title') }}</h3>
                <p class="text-sm text-muted mt-1">{{ __('documents.lib_empty_body') }}</p>
            @endif
        </div>
    </x-ui.card>
@elseif($view === 'grouped')
    @include('citizen.documents._grouped')
@else
    @include('citizen.documents._flat')
@endif

<div class="mt-6">
    {{ $documents->links() }}
</div>

@endsection
