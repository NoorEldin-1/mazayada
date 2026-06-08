@extends('layouts.citizen')
@section('title', __('dashboard.nav_appeals'))
@section('content')

<x-ui.page-header :title="__('dashboard.nav_appeals')" />

@if(session('success'))
<div class="mb-5 rounded-xl bg-ok/10 text-ok px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

<div class="grid lg:grid-cols-2 gap-5">
    {{-- My Appeals List --}}
    <x-ui.card :title="__('appeals.submitted_list')">
        @forelse($appeals ?? [] as $appeal)
        <div class="p-3.5 border border-line rounded-xl mb-2.5">
            <div class="flex items-center justify-between mb-2">
                <strong class="text-sm">{{ $appeal->subject }}</strong>
                <span class="chip {{ $appeal->status->chipClass() }}"><span class="dot"></span>{{ $appeal->status->label() }}</span>
            </div>
            <p class="text-[13px] text-muted mb-1.5">{{ Str::limit($appeal->reason, 100) }}</p>
            <div class="text-[11px] text-muted flex gap-3">
                @if($appeal->auction_id)<span>{{ __('appeals.auction_ref') }} {{ Str::limit($appeal->auction?->title_ar, 30) }}</span>@endif
                <span>{{ $appeal->created_at->format('Y-m-d') }}</span>
            </div>
            @if($appeal->admin_response)
            <div class="mt-2.5 p-2.5 bg-bg-2 rounded-lg text-sm">
                <strong class="text-primary">{{ __('appeals.admin_response') }}</strong> {{ $appeal->admin_response }}
            </div>
            @endif
        </div>
        @empty
        <div class="text-center text-muted py-8">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mx-auto mb-3 block opacity-30"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <p class="text-sm">{{ __('appeals.none_submitted') }}</p>
        </div>
        @endforelse
    </x-ui.card>

    {{-- New Appeal Form --}}
    <x-ui.card :title="__('appeals.new_title')">
        <form action="{{ route('citizen.appeals.store') }}" method="POST">
            @csrf
            <div class="field" style="margin-bottom:14px">
                <label>{{ __('appeals.subject') }} <span class="req">*</span></label>
                <input class="input" name="subject" value="{{ old('subject') }}" placeholder="{{ __('appeals.subject_placeholder') }}" required>
                @error('subject')<small class="text-danger text-xs mt-1">{{ $message }}</small>@enderror
            </div>
            <div class="field" style="margin-bottom:14px">
                <label>{{ __('appeals.details') }} <span class="req">*</span></label>
                <textarea class="textarea" name="reason" rows="5" placeholder="{{ __('appeals.details_placeholder') }}" required>{{ old('reason') }}</textarea>
                @error('reason')<small class="text-danger text-xs mt-1">{{ $message }}</small>@enderror
            </div>
            <div class="field" style="margin-bottom:20px">
                <label>{{ __('appeals.auction_ref_field') }} <span class="hint">({{ __('common.optional') }})</span></label>
                <select class="input" name="auction_id">
                    <option value="">{{ __('appeals.no_ref') }}</option>
                    @foreach(auth()->user()->participations()->with('auction')->get() as $p)
                        <option value="{{ $p->auction_id }}">{{ $p->auction->title_ar }}</option>
                    @endforeach
                </select>
            </div>
            <x-ui.btn variant="primary" class="w-full">{{ __('appeals.submit') }}</x-ui.btn>
        </form>
    </x-ui.card>
</div>

@endsection
