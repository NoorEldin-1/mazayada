{{-- Grouped view: one card per auction, holding that auction's documents. --}}
<div class="space-y-4">
    @foreach($grouped as $group)
        @php $auction = $group['auction']; @endphp
        <x-ui.card :padding="false">
            <x-slot:header>
                <div class="grid place-items-center size-10 rounded-xl bg-primary/10 text-primary shrink-0">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-bold text-ink truncate">{{ $auction?->localizedTitle() ?? __('documents.lib_unlinked') }}</div>
                    @if($auction)
                        <div class="text-xs text-muted mt-0.5 truncate">
                            {{ $auction->entity?->name }}@if($auction->entity && $auction->wilaya) · @endif{{ $auction->wilaya?->name }}
                        </div>
                    @endif
                </div>
                <div class="ms-auto flex items-center gap-3 shrink-0">
                    <span class="text-xs text-muted whitespace-nowrap">{{ __('documents.lib_docs_count', ['count' => $group['documents']->count()]) }}</span>
                    @if($auction)
                        <x-ui.btn :href="route('auctions.show', $auction)" variant="outline" size="sm">{{ __('documents.lib_view_auction') }}</x-ui.btn>
                    @endif
                </div>
            </x-slot:header>

            <div class="p-3 sm:p-4 space-y-3">
                @foreach($group['documents'] as $doc)
                    @include('citizen.documents._doc-card', ['doc' => $doc, 'showAuction' => false])
                @endforeach
            </div>
        </x-ui.card>
    @endforeach
</div>
