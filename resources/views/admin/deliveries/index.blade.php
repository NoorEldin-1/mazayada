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
                    @php($isDelivered = $delivery && $delivery->status->value === 'DELIVERED')
                    @if($isDelivered && ! $delivery->report_document_id)
                        {{-- Delivered with no report on file → no available action. --}}
                        <span class="text-muted">—</span>
                    @else
                        <x-ui.action-menu>
                            @if($isDelivered)
                                <x-ui.action-menu.item :href="route('documents.download', $delivery->report_document_id)">{{ __('deliveries.report') }}</x-ui.action-menu.item>
                            @else
                                <x-ui.action-menu.item data-modal-target="#delivery-{{ $auction->id }}">{{ $delivery ? __('deliveries.reschedule') : __('deliveries.schedule') }}</x-ui.action-menu.item>
                                @if($delivery)
                                    <x-ui.action-menu.item :action="route('admin.deliveries.deliver', $delivery)">{{ __('deliveries.mark_delivered') }}</x-ui.action-menu.item>
                                @endif
                            @endif
                        </x-ui.action-menu>
                    @endif

                    @unless($isDelivered)
                        <x-ui.modal id="delivery-{{ $auction->id }}" :title="$delivery ? __('deliveries.reschedule') : __('deliveries.schedule')">
                            <form method="POST" action="{{ route('admin.deliveries.store', $auction) }}">
                                @csrf
                                <div class="field" style="margin-bottom:.75rem">
                                    <label style="font-size:.85rem;display:block;margin-bottom:.3rem">{{ __('deliveries.scheduled_at') }}</label>
                                    <input type="datetime-local" name="scheduled_at" class="input" required>
                                </div>
                                <div class="field" style="margin-bottom:.75rem">
                                    <label style="font-size:.85rem;display:block;margin-bottom:.3rem">{{ __('deliveries.address') }}</label>
                                    <input type="text" name="address" class="input" value="{{ $auction->asset_location }}">
                                </div>
                                <div class="field" style="margin-bottom:.9rem">
                                    <label style="font-size:.85rem;display:block;margin-bottom:.3rem">{{ __('deliveries.notes') }}</label>
                                    <textarea name="notes" class="textarea" rows="2"></textarea>
                                </div>
                                <div class="flex gap-2">
                                    <x-ui.btn variant="primary" size="sm">{{ __('deliveries.save') }}</x-ui.btn>
                                    <x-ui.btn variant="ghost" size="sm" type="button" data-modal-close>{{ __('common.cancel') }}</x-ui.btn>
                                </div>
                            </form>
                        </x-ui.modal>
                    @endunless
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
