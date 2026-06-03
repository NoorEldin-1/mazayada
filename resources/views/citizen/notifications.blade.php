@extends('layouts.citizen')
@section('title', __('notifications.title'))
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
    <h2 style="font-size:24px;font-weight:700;margin:0">{{ __('notifications.title') }}</h2>
    <span class="chip chip-info"><span class="dot"></span>{{ __('notifications.unread', ['count' => $notifications->where('is_read', false)->count()]) }}</span>
</div>

<div class="card">
    <div class="card-pad" style="padding:0">
        @forelse($notifications as $notif)
        <div style="padding:16px 22px;border-bottom:1px solid var(--line);display:flex;gap:14px;align-items:flex-start;{{ !$notif->is_read ? 'background:#FAFBFD' : '' }}">
            <div style="width:40px;height:40px;border-radius:12px;display:grid;place-items:center;flex-shrink:0;{{ !$notif->is_read ? 'background:rgba(27,77,62,.08);color:var(--primary)' : 'background:#F2F4F8;color:var(--muted)' }}">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <div style="flex:1;min-width:0">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                    <strong style="font-size:14px;{{ !$notif->is_read ? 'color:var(--ink)' : 'color:var(--ink-2)' }}">{{ $notif->title }}</strong>
                    <span style="font-size:11px;color:var(--muted);white-space:nowrap">{{ $notif->created_at->diffForHumans() }}</span>
                </div>
                <p style="margin:0;font-size:13px;color:var(--muted);line-height:1.6">{{ $notif->body }}</p>
            </div>
            @if(!$notif->is_read)
            <div style="width:8px;height:8px;border-radius:50%;background:var(--primary);flex-shrink:0;margin-top:8px"></div>
            @endif
        </div>
        @empty
        <div style="text-align:center;padding:48px;color:var(--muted)">
            <p>{{ __('notifications.empty') }}</p>
        </div>
        @endforelse
    </div>
</div>

@if(method_exists($notifications, 'links'))
<div style="margin-top:20px">{{ $notifications->links() }}</div>
@endif

@endsection
