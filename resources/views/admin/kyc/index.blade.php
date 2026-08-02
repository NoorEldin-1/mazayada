@extends('layouts.admin')

@section('title', __('admin.kyc.manage_title'))
@section('page-title', __('admin.kyc.manage_title'))

@section('content')

{{-- Status tabs. The queue used to show UNDER_REVIEW only, with no hint that
     the "pending" accounts visible in the Users list are simply citizens who
     have not uploaded documents yet — which read as missing data. --}}
@php
    $tabs = [
        \App\Enums\KycStatus::UNDER_REVIEW->value => \App\Enums\KycStatus::UNDER_REVIEW->label(),
        \App\Enums\KycStatus::PENDING->value => \App\Enums\KycStatus::PENDING->label(),
        \App\Enums\KycStatus::COMPLETE->value => \App\Enums\KycStatus::COMPLETE->label(),
        \App\Enums\KycStatus::REJECTED->value => \App\Enums\KycStatus::REJECTED->label(),
        \App\Enums\KycStatus::SUSPENDED->value => \App\Enums\KycStatus::SUSPENDED->label(),
        'all' => __('common.all'),
    ];
@endphp
<div class="flex flex-wrap gap-2 mb-5">
    @foreach($tabs as $value => $label)
        @php $count = $value === 'all' ? $counts->sum() : ($counts[$value] ?? 0); @endphp
        <a href="{{ route('admin.kyc.index', $value === \App\Enums\KycStatus::UNDER_REVIEW->value ? [] : ['status' => $value]) }}"
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
            <th>{{ __('admin.th_name') }}</th>
            <th>NIN</th>
            <th>{{ __('admin.kyc.th_email_short') }}</th>
            <th>{{ __('common.status') }}</th>
            <th>{{ __('admin.kyc.th_submitted_date') }}</th>
            <th>{{ __('common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $user)
            <tr>
                <td>{{ $user->fullNameAr() }}</td>
                <td class="num" style="direction:ltr;text-align:right">{{ $user->nin }}</td>
                <td style="direction:ltr;text-align:right">{{ $user->email }}</td>
                <td><span class="chip {{ $user->kyc_status->chipClass() }}">{{ $user->kyc_status->label() }}</span></td>
                <td>{{ $user->kyc_submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                <td>
                    <x-ui.action-menu>
                        <x-ui.action-menu.item :href="route('admin.kyc.show', $user)">{{ __('admin.kyc.review') }}</x-ui.action-menu.item>
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-8">
                    {{ $activeStatus === \App\Enums\KycStatus::UNDER_REVIEW->value ? __('admin.kyc.no_pending') : __('admin.kyc.none_in_status') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</x-ui.table>

{{-- Pagination --}}
<div class="mt-6">
    {{ $users->links() }}
</div>

@endsection
