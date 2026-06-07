@extends('layouts.admin')

@section('title', __('deliveries.manage_title'))
@section('page-title', __('deliveries.manage_title'))

@section('content')

<div class="card">
    <table class="tbl">
        <thead>
            <tr>
                <th>{{ __('deliveries.th_auction') }}</th>
                <th>{{ __('deliveries.th_winner') }}</th>
                <th>{{ __('common.status') }}</th>
                <th>{{ __('deliveries.th_scheduled') }}</th>
                <th>{{ __('common.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($auctions as $auction)
                @php($delivery = $auction->delivery)
                <tr class="row-hover">
                    <td>{{ $auction->title_ar }}</td>
                    <td>{{ $auction->winner?->fullNameAr() ?? '—' }}</td>
                    <td>
                        @if($delivery)
                            <span class="chip {{ $delivery->status->chipClass() }}">{{ $delivery->status->label() }}</span>
                        @else
                            <span class="chip chip-info">{{ __('deliveries.not_scheduled') }}</span>
                        @endif
                    </td>
                    <td>{{ $delivery?->scheduled_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>
                        @if($delivery && $delivery->status->value === 'DELIVERED')
                            @if($delivery->report_document_id)
                                <a class="btn btn-ghost btn-sm" href="{{ route('documents.download', $delivery->report_document_id) }}">{{ __('deliveries.report') }}</a>
                            @endif
                        @else
                            <button type="button" class="btn btn-ghost btn-sm"
                                    onclick="document.getElementById('del-{{ $auction->id }}').style.display = document.getElementById('del-{{ $auction->id }}').style.display === 'none' ? 'block' : 'none'">
                                {{ $delivery ? __('deliveries.reschedule') : __('deliveries.schedule') }}
                            </button>
                            @if($delivery)
                                <form method="POST" action="{{ route('admin.deliveries.deliver', $delivery) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">{{ __('deliveries.mark_delivered') }}</button>
                                </form>
                            @endif
                            <div id="del-{{ $auction->id }}" style="display:none;margin-top:0.75rem">
                                <div class="card card-pad" style="background:var(--bg-subtle)">
                                    <form method="POST" action="{{ route('admin.deliveries.store', $auction) }}">
                                        @csrf
                                        <div class="field" style="margin-bottom:.6rem">
                                            <label style="font-size:.85rem">{{ __('deliveries.scheduled_at') }}</label>
                                            <input type="datetime-local" name="scheduled_at" class="input" required>
                                        </div>
                                        <div class="field" style="margin-bottom:.6rem">
                                            <label style="font-size:.85rem">{{ __('deliveries.address') }}</label>
                                            <input type="text" name="address" class="input" value="{{ $auction->asset_location }}">
                                        </div>
                                        <div class="field" style="margin-bottom:.6rem">
                                            <label style="font-size:.85rem">{{ __('deliveries.notes') }}</label>
                                            <textarea name="notes" class="textarea" rows="2"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm">{{ __('deliveries.save') }}</button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:2rem;color:var(--ink-muted)">{{ __('deliveries.none') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:1.5rem">{{ $auctions->links() }}</div>

@endsection
