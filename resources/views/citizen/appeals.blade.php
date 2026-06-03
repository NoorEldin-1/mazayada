@extends('layouts.citizen')
@section('title', __('dashboard.nav_appeals'))
@section('content')

<div style="display:flex;align-items:center;gap:14px;margin-bottom:24px">
    <h2 style="font-size:24px;font-weight:700;margin:0">{{ __('dashboard.nav_appeals') }}</h2>
</div>

@if(session('success'))
<div style="background:#E5F3EC;color:#1d6045;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:13px">{{ session('success') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    {{-- My Appeals List --}}
    <div class="card">
        <div class="card-h"><h3>{{ __('appeals.submitted_list') }}</h3></div>
        <div class="card-pad">
            @forelse($appeals ?? [] as $appeal)
            <div style="padding:14px;border:1px solid var(--line);border-radius:12px;margin-bottom:10px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                    <strong style="font-size:14px">{{ $appeal->subject }}</strong>
                    <span class="chip {{ $appeal->status->chipClass() }}"><span class="dot"></span>{{ $appeal->status->label() }}</span>
                </div>
                <p style="font-size:13px;color:var(--muted);margin:0 0 6px">{{ Str::limit($appeal->reason, 100) }}</p>
                <div style="font-size:11px;color:var(--muted);display:flex;gap:12px">
                    @if($appeal->auction_id)<span>{{ __('appeals.auction_ref') }} {{ Str::limit($appeal->auction?->title_ar, 30) }}</span>@endif
                    <span>{{ $appeal->created_at->format('Y-m-d') }}</span>
                </div>
                @if($appeal->admin_response)
                <div style="margin-top:10px;padding:10px;background:#F7F8FB;border-radius:8px;font-size:13px">
                    <strong style="color:var(--primary)">{{ __('appeals.admin_response') }}</strong> {{ $appeal->admin_response }}
                </div>
                @endif
            </div>
            @empty
            <div style="text-align:center;padding:32px;color:var(--muted)">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;opacity:.3"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <p style="font-size:14px">{{ __('appeals.none_submitted') }}</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- New Appeal Form --}}
    <div class="card">
        <div class="card-h"><h3>{{ __('appeals.new_title') }}</h3></div>
        <div class="card-pad">
            <form action="{{ route('citizen.appeals.store') }}" method="POST">
                @csrf
                <div class="field" style="margin-bottom:14px">
                    <label>{{ __('appeals.subject') }} <span class="req">*</span></label>
                    <input class="input" name="subject" value="{{ old('subject') }}" placeholder="{{ __('appeals.subject_placeholder') }}" required>
                    @error('subject')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                </div>
                <div class="field" style="margin-bottom:14px">
                    <label>{{ __('appeals.details') }} <span class="req">*</span></label>
                    <textarea class="textarea" name="reason" rows="5" placeholder="{{ __('appeals.details_placeholder') }}" required>{{ old('reason') }}</textarea>
                    @error('reason')<small style="color:var(--danger)">{{ $message }}</small>@enderror
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
                <button type="submit" class="btn btn-primary btn-block">{{ __('appeals.submit') }}</button>
            </form>
        </div>
    </div>
</div>

@endsection
