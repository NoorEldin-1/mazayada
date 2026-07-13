@extends('layouts.admin')

@section('title', __('auction_reports.manage_title'))
@section('page-title', __('auction_reports.manage_title'))

@section('content')

@if(session('success'))
    <div class="mb-5 rounded-xl bg-ok/10 text-ok px-4 py-3 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-5 rounded-xl bg-danger/10 text-danger px-4 py-3 text-sm">{{ session('error') }}</div>
@endif

{{-- Platform admin sees every report + can refer; the organising entity sees
     only reports referred to it and may only view them. --}}
<x-ui.table>
    <thead>
        <tr>
            <th>{{ __('auction_reports.th_seq') }}</th>
            <th>{{ __('auction_reports.th_auction') }}</th>
            <th>{{ __('auction_reports.th_status') }}</th>
            @unless($isEntity)
                <th>{{ __('auction_reports.th_generated_by') }}</th>
                <th>{{ __('auction_reports.th_referral') }}</th>
            @endunless
            <th>{{ __('common.date') }}</th>
            <th>{{ __('common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reports as $report)
            <tr>
                <td class="num">#{{ $report->sequence_no }}</td>
                <td>{{ $report->auction?->title_ar ?? '—' }}</td>
                <td>
                    @php $s = data_get($report->snapshot, 'status'); @endphp
                    <span class="chip chip-muted">{{ $s ? __('enums.auction_status.'.$s) : '—' }}</span>
                </td>
                @unless($isEntity)
                    <td>{{ $report->generatedBy?->fullNameAr() ?? '—' }}</td>
                    <td>
                        @if($report->isReferred())
                            <span class="chip chip-ok">{{ __('auction_reports.referred_badge') }}</span>
                        @else
                            <span class="chip chip-muted">{{ __('auction_reports.not_referred_badge') }}</span>
                        @endif
                    </td>
                @endunless
                <td>{{ $report->created_at->format('Y-m-d H:i') }}</td>
                <td>
                    <x-ui.action-menu>
                        @can('view', $report)
                            <x-ui.action-menu.item :href="route('admin.auction-reports.view', $report)" target="_blank" rel="noopener">{{ __('auction_reports.action_view') }}</x-ui.action-menu.item>
                        @endcan
                        @can('refer', $report)
                            @unless($report->isReferred())
                                <x-ui.action-menu.item :action="route('admin.auction-reports.refer', $report)"
                                    :confirm="__('auction_reports.confirm_refer')" :confirm-label="__('auction_reports.action_refer')">{{ __('auction_reports.action_refer') }}</x-ui.action-menu.item>
                            @endunless
                        @endcan
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $isEntity ? 5 : 7 }}" class="text-center text-muted py-8">{{ __('auction_reports.none') }}</td>
            </tr>
        @endforelse
    </tbody>
</x-ui.table>

<div class="mt-6">
    {{ $reports->links() }}
</div>

@endsection
