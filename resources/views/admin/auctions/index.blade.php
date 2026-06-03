@extends('layouts.admin')

@section('title', __('admin.auctions.manage_title'))
@section('page-title', __('admin.auctions.manage_title'))

@section('content')

{{-- Header Actions --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
    <div></div>
    <a href="{{ route('admin.auctions.create') }}" class="btn btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ __('admin.create_auction') }}
    </a>
</div>

{{-- Filter Tabs --}}
<div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
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
<div class="card">
    <table class="tbl">
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
                <tr class="row-hover">
                    <td>{{ $auction->title_ar }}</td>
                    <td>{{ $auction->entity?->name ?? '—' }}</td>
                    <td>{{ $auction->category?->name ?? '—' }}</td>
                    <td class="num">{{ dzd($auction->opening_price) }}</td>
                    <td class="num">{{ $auction->bidCount() }}</td>
                    <td>
                        <span class="chip {{ $auction->status->chipClass() }}">{{ $auction->status->label() }}</span>
                    </td>
                    <td>
                        <div style="display:flex;gap:0.375rem;flex-wrap:wrap">
                            @if($auction->status === \App\Enums\AuctionStatus::DRAFT)
                                {{-- Publish --}}
                                <form method="POST" action="{{ route('admin.auctions.publish', $auction) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.auctions.publish') }}</button>
                                </form>
                                {{-- Edit --}}
                                <a href="{{ route('admin.auctions.edit', $auction) }}" class="btn btn-ghost btn-sm">{{ __('common.edit') }}</a>
                                {{-- Delete --}}
                                <form method="POST" action="{{ route('admin.auctions.destroy', $auction) }}" onsubmit="return confirm('{{ __('admin.auctions.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red-600)">{{ __('common.delete') }}</button>
                                </form>
                            @elseif($auction->status === \App\Enums\AuctionStatus::PUBLISHED)
                                {{-- Start --}}
                                <form method="POST" action="{{ route('admin.auctions.start', $auction) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-accent btn-sm">{{ __('admin.auctions.start') }}</button>
                                </form>
                            @else
                                <a href="{{ route('auctions.show', $auction) }}" class="btn btn-ghost btn-sm">{{ __('common.view') }}</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:2rem;color:var(--ink-muted)">{{ __('admin.auctions.no_auctions') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div style="margin-top:1.5rem">
    {{ $auctions->links() }}
</div>

@endsection
