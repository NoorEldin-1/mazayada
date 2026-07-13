@extends('layouts.admin')

@section('title', __('admin.auctions.manage_title'))
@section('page-title', __('admin.auctions.manage_title'))

@section('content')

{{-- Header Actions --}}
@can('auctions.create')
<div class="flex justify-end mb-5">
    <x-ui.btn variant="primary" :href="route('admin.auctions.create')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ __('admin.create_auction') }}
    </x-ui.btn>
</div>
@endcan

{{-- Filter Tabs --}}
<div class="flex flex-wrap gap-2 mb-5">
    <a href="{{ route('admin.auctions.index') }}"
       class="chip {{ !request('status') ? 'chip-info' : 'chip-muted' }}">
        {{ __('common.all') }}
    </a>
    <a href="{{ route('admin.auctions.index', ['status' => 'DRAFT']) }}"
       class="chip {{ request('status') === 'DRAFT' ? 'chip-info' : 'chip-muted' }}">
        {{ __('enums.auction_status.DRAFT') }}
    </a>
    <a href="{{ route('admin.auctions.index', ['status' => 'PUBLISHED']) }}"
       class="chip {{ request('status') === 'PUBLISHED' ? 'chip-info' : 'chip-muted' }}">
        {{ __('enums.auction_status.PUBLISHED') }}
    </a>
    <a href="{{ route('admin.auctions.index', ['status' => 'ACTIVE']) }}"
       class="chip {{ request('status') === 'ACTIVE' ? 'chip-info' : 'chip-muted' }}">
        {{ __('enums.auction_status.ACTIVE') }}
    </a>
    <a href="{{ route('admin.auctions.index', ['status' => 'CLOSED']) }}"
       class="chip {{ request('status') === 'CLOSED' ? 'chip-info' : 'chip-muted' }}">
        {{ __('enums.auction_status.CLOSED') }}
    </a>
</div>

{{-- Auctions Table --}}
<x-ui.table>
    <thead>
        <tr>
            <th>{{ __('admin.th_title') }}</th>
            <th>{{ __('admin.th_entity') }}</th>
            <th>{{ __('admin.th_category') }}</th>
            <th>{{ __('admin.th_price') }}</th>
            <th>{{ __('admin.th_bids') }}</th>
            <th>{{ __('admin.th_status') }}</th>
            <th>{{ __('common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($auctions as $auction)
            <tr>
                <td>{{ $auction->title_ar }}</td>
                <td>{{ $auction->entity?->name ?? '—' }}</td>
                <td>{{ $auction->category?->name ?? '—' }}</td>
                <td class="num"><x-money :centimes="$auction->opening_price" /></td>
                <td class="num">{{ $auction->bidCount() }}</td>
                <td>
                    <span class="chip {{ $auction->status->chipClass() }}">{{ $auction->status->label() }}</span>
                </td>
                <td>
                    <x-ui.action-menu>
                        {{-- Read-only full detail — available to every role that can view the auction. --}}
                        @can('view', $auction)
                            <x-ui.action-menu.item :href="route('admin.auctions.show', $auction)">{{ __('admin.auctions.view_details') }}</x-ui.action-menu.item>
                        @endcan
                        {{-- تقرير المزاد — issue a fresh full-detail PDF or view the last one.
                             Available for any auction that has left DRAFT. --}}
                        @if($auction->status !== \App\Enums\AuctionStatus::DRAFT)
                            @can('generate', $auction)
                                <x-ui.action-menu.submenu :label="__('auction_reports.menu_report')">
                                    <x-ui.action-menu.item :action="route('admin.auctions.reports.generate', $auction)">{{ __('auction_reports.action_generate') }}</x-ui.action-menu.item>
                                    <x-ui.action-menu.item :href="route('admin.auctions.reports.latest', $auction)" target="_blank" rel="noopener">{{ __('auction_reports.action_view') }}</x-ui.action-menu.item>
                                </x-ui.action-menu.submenu>
                            @endcan
                        @endif
                        {{-- §4 step 2 — condition book: generate (then download) for any pre-close auction --}}
                        @can('documents.generate')
                            @if(in_array($auction->status, [\App\Enums\AuctionStatus::DRAFT, \App\Enums\AuctionStatus::PUBLISHED, \App\Enums\AuctionStatus::ACTIVE, \App\Enums\AuctionStatus::EXTENDED], true))
                                @php $cb = $auction->documents()->where('type', 'CONDITION_BOOK')->latest()->first(); @endphp
                                @if($cb)
                                    <x-ui.action-menu.item :href="route('documents.download', $cb)">↓ {{ __('admin.auctions.cb_download') }}</x-ui.action-menu.item>
                                @else
                                    <x-ui.action-menu.item :action="route('admin.auctions.condition-book', $auction)">{{ __('admin.auctions.cb_generate') }}</x-ui.action-menu.item>
                                @endif
                            @endif
                        @endcan
                        @if($auction->status === \App\Enums\AuctionStatus::DRAFT)
                            {{-- Publish --}}
                            @can('publish', $auction)
                                <x-ui.action-menu.item :action="route('admin.auctions.publish', $auction)">{{ __('admin.auctions.publish') }}</x-ui.action-menu.item>
                            @endcan
                            {{-- Edit --}}
                            @can('update', $auction)
                                <x-ui.action-menu.item :href="route('admin.auctions.edit', $auction)">{{ __('common.edit') }}</x-ui.action-menu.item>
                            @endcan
                            {{-- Delete --}}
                            @can('delete', $auction)
                                <x-ui.action-menu.item :action="route('admin.auctions.destroy', $auction)" method="DELETE" variant="danger"
                                    :confirm="__('admin.auctions.confirm_delete')" confirm-variant="danger" :confirm-label="__('common.delete')">{{ __('common.delete') }}</x-ui.action-menu.item>
                            @endcan
                        @elseif($auction->status === \App\Enums\AuctionStatus::PUBLISHED)
                            {{-- Start --}}
                            @can('start', $auction)
                                <x-ui.action-menu.item :action="route('admin.auctions.start', $auction)">{{ __('admin.auctions.start') }}</x-ui.action-menu.item>
                            @endcan
                            @can('cancel', $auction)
                                <x-ui.action-menu.item :action="route('admin.auctions.cancel', $auction)" variant="danger"
                                    :confirm="__('admin.auctions.confirm_cancel')" confirm-variant="danger">{{ __('admin.auctions.cancel') }}</x-ui.action-menu.item>
                            @endcan
                        @elseif(in_array($auction->status, [\App\Enums\AuctionStatus::ACTIVE, \App\Enums\AuctionStatus::EXTENDED], true))
                            <x-ui.action-menu.item :href="route('auctions.show', $auction)">{{ __('common.view') }}</x-ui.action-menu.item>
                            @can('extend', $auction)
                                <x-ui.action-menu.item :action="route('admin.auctions.extend', $auction)" :confirm="__('admin.auctions.confirm_extend')">{{ __('admin.auctions.extend') }}</x-ui.action-menu.item>
                            @endcan
                            @can('cancel', $auction)
                                <x-ui.action-menu.item :action="route('admin.auctions.cancel', $auction)" variant="danger"
                                    :confirm="__('admin.auctions.confirm_cancel')" confirm-variant="danger">{{ __('admin.auctions.cancel') }}</x-ui.action-menu.item>
                            @endcan
                        @else
                            <x-ui.action-menu.item :href="route('auctions.show', $auction)">{{ __('common.view') }}</x-ui.action-menu.item>
                        @endif
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-8">{{ __('admin.auctions.no_auctions') }}</td>
            </tr>
        @endforelse
    </tbody>
</x-ui.table>

{{-- Pagination --}}
<div class="mt-6">
    {{ $auctions->links() }}
</div>

@endsection
