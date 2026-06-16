@extends('layouts.citizen')
@section('title', __('notifications.title'))
@section('content')

<x-ui.page-header :title="__('notifications.title')">
    <x-slot:actions>
        <span class="chip chip-info"><span class="dot"></span>{{ __('notifications.unread', ['count' => $unreadCount]) }}</span>
        @if($unreadCount > 0)
        <form method="POST" action="{{ route('citizen.notifications.read-all') }}">
            @csrf
            <x-ui.btn variant="ghost" size="sm">{{ __('notifications.mark_all_read') }}</x-ui.btn>
        </form>
        @endif
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card :padding="false">
    @forelse($notifications as $notif)
    <div class="relative flex gap-3.5 items-start px-5 py-4 border-b border-line last:border-b-0 transition hover:bg-bg-2/50 {{ !$notif->is_read ? 'bg-primary/5' : '' }}">
        {{-- The icon + text are the clickable target. A bare <a> (no href) stays
             inert when the notification has no action_url. The mark-read form is
             kept OUTSIDE the anchor (nesting a form in <a> is invalid HTML). --}}
        <a @if($notif->action_url) href="{{ $notif->action_url }}" @endif
           class="flex gap-3.5 items-start flex-1 min-w-0 {{ $notif->action_url ? '' : 'cursor-default' }}">
            <div class="w-10 h-10 rounded-xl grid place-items-center shrink-0 {{ !$notif->is_read ? 'bg-primary/10 text-primary' : 'bg-bg-2 text-muted' }}">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-center gap-3 mb-1">
                    <strong class="text-sm {{ !$notif->is_read ? 'text-ink' : 'text-ink-2' }}">{{ $notif->title }}</strong>
                    {{-- Guard against future-dated rows (clock/timezone skew): a
                         past event should never read as "an hour from now". --}}
                    <span class="text-[11px] text-muted whitespace-nowrap shrink-0">{{ $notif->created_at->isFuture() ? __('notifications.just_now') : $notif->created_at->diffForHumans() }}</span>
                </div>
                <p class="m-0 text-[13px] text-muted leading-relaxed">{{ $notif->body }}</p>
            </div>
        </a>
        @if(!$notif->is_read)
        <form method="POST" action="{{ route('citizen.notifications.read', $notif) }}" class="shrink-0">
            @csrf
            <button type="submit" title="{{ __('notifications.mark_read') }}" aria-label="{{ __('notifications.mark_read') }}" class="bg-transparent border-0 cursor-pointer p-1">
                <span class="block w-2 h-2 rounded-full bg-primary"></span>
            </button>
        </form>
        @endif
    </div>
    @empty
    <div class="text-center text-muted py-12">
        <p>{{ __('notifications.empty') }}</p>
    </div>
    @endforelse
</x-ui.card>

@if(method_exists($notifications, 'links'))
<div class="mt-5">{{ $notifications->links() }}</div>
@endif

@endsection
