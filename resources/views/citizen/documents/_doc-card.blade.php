{{--
    A single document row/card. Shared by the flat grid and the grouped auction
    cards. Expects: $doc (Document), $iconTone (map), and optional $showAuction.

    $showAuction defaults to true (flat view); the grouped view passes false since
    the auction is already the card header.
--}}
@php
    use App\Enums\DocumentType;
    $showAuction = $showAuction ?? true;
    $tone = $iconTone[$doc->type?->value] ?? 'bg-bg-2 text-ink-2';
    $badgeTone = [
        DocumentType::CONDITION_BOOK->value => 'info',
        DocumentType::AWARD->value          => 'accent',
        DocumentType::PAYMENT_RECEIPT->value => 'ok',
        DocumentType::DELIVERY_REPORT->value => 'primary',
    ][$doc->type?->value] ?? 'muted';
@endphp

<div class="flex items-start gap-3 p-4 rounded-xl border border-line bg-bg hover:border-primary/40 transition">
    <div class="grid place-items-center size-11 rounded-2xl shrink-0 {{ $tone }} [&>svg]:size-5">
        @switch($doc->type)
            @case(DocumentType::CONDITION_BOOK)
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                @break
            @case(DocumentType::AWARD)
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
                @break
            @case(DocumentType::PAYMENT_RECEIPT)
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8M8 11h8M8 15h5"/></svg>
                @break
            @default
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 18H3c-.6 0-1-.4-1-1V7c0-.6.4-1 1-1h10c.6 0 1 .4 1 1v11"/><path d="M14 9h4l4 4v4c0 .6-.4 1-1 1h-2"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
        @endswitch
    </div>

    <div class="min-w-0 flex-1">
        <x-ui.badge :variant="$badgeTone" class="mb-1.5">{{ $doc->type?->label() }}</x-ui.badge>
        <div class="text-sm font-semibold text-ink truncate" title="{{ $doc->title }}">{{ $doc->title }}</div>

        @if($showAuction && $doc->auction)
            <a href="{{ route('auctions.show', $doc->auction) }}" class="inline-flex items-center gap-1 text-xs text-muted hover:text-primary transition mt-0.5 truncate">
                <svg class="size-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                <span class="truncate">{{ $doc->auction->localizedTitle() }}</span>
            </a>
        @endif

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-muted mt-2">
            <span class="inline-flex items-center gap-1">
                <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <span class="num">{{ $doc->created_at?->format('Y-m-d') }}</span>
            </span>
            <span class="inline-flex items-center gap-1 num">{{ human_filesize($doc->file_size) }}</span>
        </div>
    </div>

    <div class="flex flex-col items-stretch gap-1.5 shrink-0">
        <x-ui.btn :href="route('documents.download', $doc)" variant="primary" size="sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            {{ __('documents.lib_download') }}
        </x-ui.btn>
        @if($doc->signature)
            <x-ui.btn :href="route('documents.verify', ['doc' => $doc->id, 'sig' => $doc->signature])" target="_blank" rel="noopener" variant="ghost" size="sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><path d="M12 3a9 9 0 1 0 9 9"/></svg>
                {{ __('documents.lib_verify') }}
            </x-ui.btn>
        @endif
    </div>
</div>
