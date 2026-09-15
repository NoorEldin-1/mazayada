@extends('layouts.admin')

@section('title', __('email_recovery.admin.title'))
@section('page-title', __('email_recovery.admin.title'))

@section('content')

@php
    $tabs = [
        'open' => __('email_recovery.admin.tab_open'),
        \App\Enums\EmailRecoveryStatus::PENDING->value => \App\Enums\EmailRecoveryStatus::PENDING->label(),
        \App\Enums\EmailRecoveryStatus::UNDER_REVIEW->value => \App\Enums\EmailRecoveryStatus::UNDER_REVIEW->label(),
        \App\Enums\EmailRecoveryStatus::APPROVED->value => \App\Enums\EmailRecoveryStatus::APPROVED->label(),
        \App\Enums\EmailRecoveryStatus::REJECTED->value => \App\Enums\EmailRecoveryStatus::REJECTED->label(),
        'all' => __('common.all'),
    ];
    $openCount = ($counts['PENDING'] ?? 0) + ($counts['UNDER_REVIEW'] ?? 0);
@endphp

<p class="text-sm text-muted mb-4">{{ __('email_recovery.admin.intro') }}</p>

<div class="flex flex-wrap gap-2 mb-5">
    @foreach($tabs as $value => $label)
        @php $count = match ($value) { 'all' => $counts->sum(), 'open' => $openCount, default => $counts[$value] ?? 0 }; @endphp
        <a href="{{ route('admin.email-recovery.index', $value === 'open' ? [] : ['status' => $value]) }}"
           class="inline-flex items-center gap-2 rounded-xl border px-3 py-1.5 text-xs font-semibold transition
                  {{ $activeStatus === $value ? 'border-primary bg-primary/10 text-primary' : 'border-line bg-surface text-ink-2 hover:bg-bg-2' }}">
            {{ $label }}
            <span class="num opacity-70">{{ $count }}</span>
        </a>
    @endforeach
</div>

<x-ui.table>
    <thead>
        <tr>
            <th>{{ __('email_recovery.admin.th_name') }}</th>
            <th>NIN</th>
            <th>{{ __('email_recovery.admin.th_old_email') }}</th>
            <th>{{ __('email_recovery.admin.th_new_email') }}</th>
            <th>{{ __('common.status') }}</th>
            <th>{{ __('email_recovery.admin.th_submitted') }}</th>
            <th>{{ __('common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($requests as $item)
            <tr>
                <td>{{ $item->user?->fullNameAr() ?: '—' }}</td>
                <td class="num" dir="ltr" style="text-align:start">{{ $item->nin }}</td>
                <td dir="ltr" style="text-align:start">{{ $item->old_email ?: '—' }}</td>
                <td dir="ltr" style="text-align:start">{{ $item->new_email }}</td>
                <td><span class="chip {{ $item->status->chipClass() }}">{{ $item->status->label() }}</span></td>
                <td class="num">{{ $item->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                <td>
                    <x-ui.action-menu>
                        <x-ui.action-menu.item :href="route('admin.email-recovery.show', $item)">{{ __('email_recovery.admin.review') }}</x-ui.action-menu.item>
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-8">{{ __('email_recovery.admin.empty') }}</td>
            </tr>
        @endforelse
    </tbody>
</x-ui.table>

<div class="mt-6">
    {{ $requests->links() }}
</div>

@endsection
