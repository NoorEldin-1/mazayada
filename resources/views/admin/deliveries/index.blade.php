@extends('layouts.admin')

@section('title', __('deliveries.manage_title'))
@section('page-title', __('deliveries.manage_title'))

@section('content')

<x-ui.table>
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
            <tr>
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
                            <x-ui.btn variant="ghost" size="sm" :href="route('documents.download', $delivery->report_document_id)">{{ __('deliveries.report') }}</x-ui.btn>
                        @endif
                    @else
                        <x-ui.btn variant="ghost" size="sm" type="button"
                                onclick="document.getElementById('del-{{ $auction->id }}').style.display = document.getElementById('del-{{ $auction->id }}').style.display === 'none' ? 'block' : 'none'">
                            {{ $delivery ? __('deliveries.reschedule') : __('deliveries.schedule') }}
                        </x-ui.btn>
                        @if($delivery)
                            <form method="POST" action="{{ route('admin.deliveries.deliver', $delivery) }}" style="display:inline">
                                @csrf
                                <x-ui.btn variant="primary" size="sm">{{ __('deliveries.mark_delivered') }}</x-ui.btn>
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
                                    <x-ui.btn variant="primary" size="sm">{{ __('deliveries.save') }}</x-ui.btn>
                                </form>
                            </div>
                        </div>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted py-8">{{ __('deliveries.none') }}</td>
            </tr>
        @endforelse
    </tbody>
</x-ui.table>

<div class="mt-6">{{ $auctions->links() }}</div>

@endsection
