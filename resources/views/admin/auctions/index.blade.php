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
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Read-only full detail — available to every role that can view the auction. --}}
                        @can('view', $auction)
                            <x-ui.btn variant="ghost" size="sm" :href="route('admin.auctions.show', $auction)">{{ __('admin.auctions.view_details') }}</x-ui.btn>
                        @endcan
                        {{-- §4 step 2 — condition book: generate (then download) for any pre-close auction --}}
                        @can('documents.generate')
                            @if(in_array($auction->status, [\App\Enums\AuctionStatus::DRAFT, \App\Enums\AuctionStatus::PUBLISHED, \App\Enums\AuctionStatus::ACTIVE, \App\Enums\AuctionStatus::EXTENDED], true))
                                @php $cb = $auction->documents()->where('type', 'CONDITION_BOOK')->latest()->first(); @endphp
                                @if($cb)
                                    <x-ui.btn variant="ghost" size="sm" :href="route('documents.download', $cb)">↓ {{ __('admin.auctions.cb_download') }}</x-ui.btn>
                                @else
                                    <form method="POST" action="{{ route('admin.auctions.condition-book', $auction) }}">
                                        @csrf
                                        <x-ui.btn variant="ghost" size="sm">{{ __('admin.auctions.cb_generate') }}</x-ui.btn>
                                    </form>
                                @endif
                            @endif
                        @endcan
                        @if($auction->status === \App\Enums\AuctionStatus::DRAFT)
                            {{-- Publish --}}
                            @can('publish', $auction)
                            <form method="POST" action="{{ route('admin.auctions.publish', $auction) }}">
                                @csrf
                                <x-ui.btn variant="primary" size="sm">{{ __('admin.auctions.publish') }}</x-ui.btn>
                            </form>
                            @endcan
                            {{-- Edit --}}
                            @can('update', $auction)
                            <x-ui.btn variant="ghost" size="sm" :href="route('admin.auctions.edit', $auction)">{{ __('common.edit') }}</x-ui.btn>
                            @endcan
                            {{-- Delete --}}
                            @can('delete', $auction)
                            <form method="POST" action="{{ route('admin.auctions.destroy', $auction) }}" data-confirm="{{ __('admin.auctions.confirm_delete') }}" data-confirm-variant="danger" data-confirm-label="{{ __('common.delete') }}">
                                @csrf
                                @method('DELETE')
                                <x-ui.btn variant="danger-ghost" size="sm">{{ __('common.delete') }}</x-ui.btn>
                            </form>
                            @endcan
                        @elseif($auction->status === \App\Enums\AuctionStatus::PUBLISHED)
                            {{-- Start --}}
                            @can('start', $auction)
                            <form method="POST" action="{{ route('admin.auctions.start', $auction) }}">
                                @csrf
                                <x-ui.btn variant="accent" size="sm">{{ __('admin.auctions.start') }}</x-ui.btn>
                            </form>
                            @endcan
                            @can('cancel', $auction)
                            <form method="POST" action="{{ route('admin.auctions.cancel', $auction) }}" data-confirm="{{ __('admin.auctions.confirm_cancel') }}" data-confirm-variant="danger">
                                @csrf
                                <x-ui.btn variant="danger-ghost" size="sm">{{ __('admin.auctions.cancel') }}</x-ui.btn>
                            </form>
                            @endcan
                        @elseif(in_array($auction->status, [\App\Enums\AuctionStatus::ACTIVE, \App\Enums\AuctionStatus::EXTENDED], true))
                            <x-ui.btn variant="ghost" size="sm" :href="route('auctions.show', $auction)">{{ __('common.view') }}</x-ui.btn>
                            @can('extend', $auction)
                            <form method="POST" action="{{ route('admin.auctions.extend', $auction) }}" data-confirm="{{ __('admin.auctions.confirm_extend') }}">
                                @csrf
                                <x-ui.btn variant="ghost" size="sm">{{ __('admin.auctions.extend') }}</x-ui.btn>
                            </form>
                            @endcan
                            @can('cancel', $auction)
                            <form method="POST" action="{{ route('admin.auctions.cancel', $auction) }}" data-confirm="{{ __('admin.auctions.confirm_cancel') }}" data-confirm-variant="danger">
                                @csrf
                                <x-ui.btn variant="danger-ghost" size="sm">{{ __('admin.auctions.cancel') }}</x-ui.btn>
                            </form>
                            @endcan
                        @else
                            <x-ui.btn variant="ghost" size="sm" :href="route('auctions.show', $auction)">{{ __('common.view') }}</x-ui.btn>
                        @endif
                    </div>
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
