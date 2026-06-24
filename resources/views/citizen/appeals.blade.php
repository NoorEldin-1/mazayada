@extends('layouts.citizen')
@section('title', __('dashboard.nav_appeals'))
@section('content')

<x-ui.page-header :title="__('dashboard.nav_appeals')" />

@if(session('success'))
<div class="mb-5 rounded-xl bg-ok/10 text-ok px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

{{-- Appeals are filed from the auction page (§ الطعون tab); this is read-only history. --}}
<x-ui.card :title="__('appeals.submitted_list')">
    @forelse($appeals ?? [] as $appeal)
    <div class="p-3.5 border border-line rounded-xl mb-2.5">
        <div class="flex items-center justify-between mb-2">
            <strong class="text-sm">{{ $appeal->subject }}</strong>
            <span class="chip {{ $appeal->status->publicChipClass() }}"><span class="dot"></span>{{ $appeal->status->publicLabel() }}</span>
        </div>
        <p class="text-[13px] text-muted mb-1.5">{{ Str::limit($appeal->reason, 100) }}</p>
        <div class="text-[11px] text-muted flex gap-3">
            @if($appeal->auction)
            <a href="{{ route('auctions.show', $appeal->auction) }}" class="text-primary hover:underline">{{ __('appeals.auction_ref') }} {{ Str::limit($appeal->auction->localizedTitle(), 30) }}</a>
            @endif
            <span>{{ $appeal->created_at->format('Y-m-d') }}</span>
        </div>
        @if($appeal->status->isTerminal() && $appeal->admin_response)
        <div class="mt-2.5 p-2.5 bg-bg-2 rounded-lg text-sm">
            <strong class="text-primary">{{ __('appeals.admin_response') }}</strong> {{ $appeal->admin_response }}
        </div>
        @endif
    </div>
    @empty
    <div class="text-center text-muted py-8">
        <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mx-auto mb-3 block opacity-30"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <p class="text-sm">{{ __('appeals.none_submitted') }}</p>
        <p class="text-xs mt-1.5">{{ __('appeals.file_from_auction_hint') }}</p>
    </div>
    @endforelse

    @if($appeals->hasPages())
    <div class="mt-4">
        {{ $appeals->links() }}
    </div>
    @endif
</x-ui.card>

@endsection
