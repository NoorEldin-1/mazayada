{{-- Edits 6-10 — reschedule a finished session as a new, reduced-price session. --}}
@php
    $sessions = app(\App\Services\AuctionSessionService::class);
@endphp
@if($sessions->canReschedule($auction))
<x-ui.modal id="reschedule-{{ $auction->id }}" :title="__('auctions.session.reschedule_title', ['round' => (int) $auction->session_round + 1])">
    <form method="POST" action="{{ route('admin.auctions.reschedule', $auction) }}">
        @csrf
        <p class="text-sm text-muted" style="margin-bottom:.9rem">{!! __('auctions.session.reschedule_hint', ['price' => dzd_html($auction->opening_price)]) !!}</p>
        @error('reschedule') <div class="text-danger text-sm" style="margin-bottom:.75rem">{{ $message }}</div> @enderror
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,200px),1fr));gap:.9rem">
            <div class="field">
                <label class="text-sm text-muted">{{ __('auctions.session.f_start') }}</label>
                <input type="datetime-local" name="start_time" class="input" value="{{ old('start_time') }}" required>
            </div>
            <div class="field">
                <label class="text-sm text-muted">{{ __('auctions.session.f_end') }}</label>
                <input type="datetime-local" name="end_time" class="input" value="{{ old('end_time') }}" required>
            </div>
            <div class="field">
                <label class="text-sm text-muted">{{ __('auctions.session.f_reduction') }}</label>
                <input type="number" name="reduction_percent" class="input num" min="0" max="90" step="0.01" value="{{ old('reduction_percent', format_percent($sessions->defaultReductionPercent())) }}">
            </div>
        </div>
        <label class="flex items-center gap-2 cursor-pointer" style="margin:.9rem 0">
            <input type="checkbox" name="publish" value="1" checked>
            <span class="text-sm">{{ __('auctions.session.f_publish') }}</span>
        </label>
        <div class="flex gap-2">
            <x-ui.btn variant="primary" size="sm">{{ __('auctions.session.submit') }}</x-ui.btn>
            <x-ui.btn variant="ghost" size="sm" type="button" data-modal-close>{{ __('common.cancel') }}</x-ui.btn>
        </div>
    </form>
</x-ui.modal>
@endif
