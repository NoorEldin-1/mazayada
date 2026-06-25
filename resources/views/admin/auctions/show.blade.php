@extends('layouts.admin')

@section('title', __('admin.auctions.detail_title'))
@section('page-title', $auction->title_ar)

@section('content')

{{-- Header: status + entity + back --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex items-center gap-3">
        <span class="chip {{ $auction->status->chipClass() }}">{{ $auction->status->label() }}</span>
        <span class="text-muted text-sm">{{ $auction->entity?->name ?? '—' }}</span>
    </div>
    <x-ui.btn variant="ghost" size="sm" :href="route('admin.auctions.index')">{{ __('admin.auctions.back_to_list') }}</x-ui.btn>
</div>

{{-- Read-only notice --}}
<div class="chip chip-info mb-5" style="display:inline-flex">{{ __('admin.auctions.read_only_banner') }}</div>

{{-- ===== Summary ===== --}}
<x-ui.card :title="__('admin.auctions.sec_summary')" class="mb-6">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
        @php
            $rows = [
                __('admin.th_category') => $auction->category?->name ?? '—',
                __('admin.auctions.f_auction_type') => $auction->auction_type?->label() ?? '—',
                __('admin.auctions.f_asset_class') => $auction->asset_class?->label() ?? '—',
                __('admin.auctions.f_wilaya') => $auction->wilaya?->name ?? '—',
                __('admin.auctions.f_commune') => $auction->commune?->name ?? '—',
                __('admin.auctions.f_asset_location') => $auction->asset_location ?: '—',
                __('admin.auctions.f_opening_price') => dzd_html($auction->opening_price),
                __('admin.auctions.current_price') => dzd_html($auction->currentPrice()),
                __('admin.auctions.f_deposit') => new \Illuminate\Support\HtmlString(dzd_html($auction->deposit_amount).' ('.rtrim(rtrim(number_format((float) $auction->deposit_percent, 2, '.', ''), '0'), '.').'%)'),
                __('admin.auctions.f_book_price') => $auction->book_price ? dzd_html($auction->book_price) : __('admin.auctions.book_free'),
                __('admin.auctions.f_start_time') => optional($auction->start_time)->format('Y-m-d H:i') ?? '—',
                __('admin.auctions.f_end_time') => optional($auction->end_time)->format('Y-m-d H:i') ?? '—',
                __('admin.auctions.winner') => $auction->winner?->fullNameAr() ?? '—',
                __('admin.auctions.final_price') => $auction->final_price ? dzd_html($auction->final_price) : '—',
                __('admin.auctions.created_by') => $auction->createdByUser?->fullNameAr() ?? '—',
                __('admin.auctions.responsible_staff') => $auction->entityUser?->full_name ?? '—',
            ];
        @endphp
        @foreach($rows as $label => $value)
            <div>
                <div class="text-xs text-muted mb-1">{{ $label }}</div>
                <div class="text-ink font-medium num">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    @if($auction->description_ar)
        <div class="mt-4">
            <div class="text-xs text-muted mb-1">{{ __('admin.auctions.f_description_ar') }}</div>
            <p class="text-ink text-sm">{{ $auction->description_ar }}</p>
        </div>
    @endif
</x-ui.card>

{{-- ===== Asset specifications (dynamic) ===== --}}
@if(!empty($auction->specifications))
<x-ui.card :title="__('admin.auctions.sec_specifications')" class="mb-6">
    <div style="display:flex;flex-direction:column">
        @foreach($auction->specifications as $spec)
            <div style="padding:0.75rem 0;{{ $loop->last ? '' : 'border-bottom:1px solid var(--line)' }}">
                <div class="text-ink font-medium mb-1">{{ $spec['title_ar'] ?? '—' }}</div>
                <p class="text-ink text-sm" style="white-space:pre-line;margin:0">{{ $spec['body_ar'] ?? '' }}</p>
                @if(!empty($spec['title_fr']) || !empty($spec['body_fr']))
                    <div class="text-xs text-muted mt-2" dir="ltr" style="text-align:start">
                        <span class="font-medium">{{ $spec['title_fr'] }}</span>
                        @if(!empty($spec['body_fr']))
                            <span style="white-space:pre-line">{{ !empty($spec['title_fr']) ? ' — ' : '' }}{{ $spec['body_fr'] }}</span>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-ui.card>
@endif

{{-- ===== Bids ===== --}}
<x-ui.card :title="__('admin.auctions.sec_bids') . ' (' . $bids->count() . ')'" class="mb-6">
    <x-ui.table>
        <thead>
            <tr>
                <th>{{ __('admin.auctions.col_bidder') }}</th>
                <th>{{ __('admin.auctions.col_amount') }}</th>
                <th>{{ __('admin.auctions.col_time') }}</th>
                <th>{{ __('admin.auctions.col_valid') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bids as $bid)
                <tr>
                    <td>{{ $bid->user?->fullNameAr() ?: '—' }}</td>
                    <td class="num"><x-money :centimes="$bid->amount" /></td>
                    <td class="num">{{ optional($bid->bid_time)->format('Y-m-d H:i') }}</td>
                    <td>
                        <span class="chip {{ $bid->is_valid ? 'chip-ok' : 'chip-muted' }}">
                            {{ $bid->is_valid ? __('common.yes') : __('common.no') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-6">{{ __('admin.auctions.none_bids') }}</td></tr>
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.card>

{{-- ===== Participants ===== --}}
<x-ui.card :title="__('admin.auctions.sec_participants') . ' (' . $participants->count() . ')'" class="mb-6">
    <x-ui.table>
        <thead>
            <tr>
                <th>{{ __('admin.auctions.col_user') }}</th>
                <th>{{ __('admin.auctions.col_deposit') }}</th>
                <th>{{ __('admin.auctions.col_book') }}</th>
                <th>{{ __('admin.auctions.col_acknowledged') }}</th>
                <th>{{ __('admin.auctions.col_original_owner') }}</th>
                <th>{{ __('admin.auctions.col_registered') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($participants as $p)
                <tr>
                    <td>{{ $p->user?->fullNameAr() ?: '—' }}</td>
                    <td><span class="chip {{ $p->deposit_paid ? 'chip-ok' : 'chip-muted' }}">{{ $p->deposit_paid ? __('common.yes') : __('common.no') }}</span></td>
                    <td><span class="chip {{ $p->book_purchased ? 'chip-ok' : 'chip-muted' }}">{{ $p->book_purchased ? __('common.yes') : __('common.no') }}</span></td>
                    <td class="num">{{ optional($p->condition_book_acknowledged_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td><span class="chip {{ $p->is_original_owner ? 'chip-warn' : 'chip-muted' }}">{{ $p->is_original_owner ? __('common.yes') : __('common.no') }}</span></td>
                    <td class="num">{{ optional($p->registered_at)->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-6">{{ __('admin.auctions.none_participants') }}</td></tr>
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.card>

{{-- ===== Payments ===== --}}
<x-ui.card :title="__('admin.auctions.sec_payments') . ' (' . $payments->count() . ')'" class="mb-6">
    <x-ui.table>
        <thead>
            <tr>
                <th>{{ __('admin.auctions.col_user') }}</th>
                <th>{{ __('admin.auctions.col_type') }}</th>
                <th>{{ __('admin.auctions.col_amount') }}</th>
                <th>{{ __('admin.auctions.col_status') }}</th>
                <th>{{ __('admin.auctions.col_confirmed_at') }}</th>
                <th>{{ __('admin.auctions.col_due_at') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $pay)
                @php
                    $payChip = [
                        'CONFIRMED' => 'chip-ok', 'PENDING' => 'chip-warn',
                        'REFUNDED' => 'chip-info', 'FORFEITED' => 'chip-danger', 'FAILED' => 'chip-danger',
                    ][$pay->status->value] ?? 'chip-muted';
                @endphp
                <tr>
                    <td>{{ $pay->user?->fullNameAr() ?: '—' }}</td>
                    <td>{{ __('enums.payment_type.' . $pay->payment_type->value) }}</td>
                    <td class="num"><x-money :centimes="$pay->amount" /></td>
                    <td><span class="chip {{ $payChip }}">{{ __('enums.payment_status.' . $pay->status->value) }}</span></td>
                    <td class="num">{{ optional($pay->confirmed_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="num">{{ optional($pay->due_at)->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-6">{{ __('admin.auctions.none_payments') }}</td></tr>
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.card>

{{-- ===== Inspection Q&A ===== --}}
<x-ui.card :title="__('admin.auctions.sec_inspection') . ' (' . $questions->count() . ')'" class="mb-6">
    <x-ui.table>
        <thead>
            <tr>
                <th>{{ __('admin.auctions.col_user') }}</th>
                <th>{{ __('admin.auctions.col_question') }}</th>
                <th>{{ __('admin.auctions.col_answer') }}</th>
                <th>{{ __('admin.auctions.col_status') }}</th>
                <th>{{ __('admin.auctions.col_public') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($questions as $q)
                <tr>
                    <td>{{ $q->user?->fullNameAr() ?: '—' }}</td>
                    <td>{{ $q->question }}</td>
                    <td>{{ $q->answer ?: '—' }}</td>
                    <td><span class="chip chip-muted">{{ $q->status->label() }}</span></td>
                    <td><span class="chip {{ $q->is_public ? 'chip-ok' : 'chip-muted' }}">{{ $q->is_public ? __('common.yes') : __('common.no') }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-6">{{ __('admin.auctions.none_questions') }}</td></tr>
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.card>

{{-- ===== Documents ===== --}}
<x-ui.card :title="__('admin.auctions.sec_documents') . ' (' . $documents->count() . ')'" class="mb-6">
    <x-ui.table>
        <thead>
            <tr>
                <th>{{ __('admin.auctions.col_document') }}</th>
                <th>{{ __('admin.auctions.col_type') }}</th>
                <th>{{ __('admin.auctions.col_public') }}</th>
                <th>{{ __('common.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $doc)
                <tr>
                    <td>{{ $doc->title ?: '—' }}</td>
                    <td>{{ $doc->type?->label() ?? '—' }}</td>
                    <td><span class="chip {{ $doc->is_public ? 'chip-ok' : 'chip-muted' }}">{{ $doc->is_public ? __('common.yes') : __('common.no') }}</span></td>
                    <td>
                        @can('documents.download')
                            <x-ui.btn variant="ghost" size="sm" :href="route('documents.download', $doc)">{{ __('common.view') }}</x-ui.btn>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-6">{{ __('admin.auctions.none_documents') }}</td></tr>
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.card>

{{-- ===== Delivery ===== --}}
<x-ui.card :title="__('admin.auctions.sec_delivery')" class="mb-6">
    @if($auction->delivery)
        @php $dl = $auction->delivery; @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
            <div><div class="text-xs text-muted mb-1">{{ __('admin.auctions.col_status') }}</div><div><span class="chip chip-info">{{ $dl->status?->label() ?? '—' }}</span></div></div>
            <div><div class="text-xs text-muted mb-1">{{ __('admin.auctions.col_user') }}</div><div class="text-ink">{{ $dl->user?->fullNameAr() ?? '—' }}</div></div>
            <div><div class="text-xs text-muted mb-1">{{ __('admin.auctions.col_scheduled') }}</div><div class="num">{{ optional($dl->scheduled_at)->format('Y-m-d H:i') ?? '—' }}</div></div>
            <div><div class="text-xs text-muted mb-1">{{ __('admin.auctions.col_delivered') }}</div><div class="num">{{ optional($dl->delivered_at)->format('Y-m-d H:i') ?? '—' }}</div></div>
            <div><div class="text-xs text-muted mb-1">{{ __('admin.auctions.col_address') }}</div><div class="text-ink">{{ $dl->address ?: '—' }}</div></div>
        </div>
    @else
        <p class="text-center text-muted py-6">{{ __('admin.auctions.no_delivery') }}</p>
    @endif
</x-ui.card>

{{-- Appeals (§ الطعون) filed against this auction — read-only; the workflow
     actions (forward / decide / confirm) live on the appeals management page. --}}
<x-ui.card :title="__('admin.auctions.sec_appeals') . ' (' . $appeals->count() . ')'" class="mb-6">
    <x-ui.table>
        <thead>
            <tr>
                <th>{{ __('appeals.th_user') }}</th>
                <th>{{ __('appeals.th_subject') }}</th>
                <th>{{ __('common.status') }}</th>
                <th>{{ __('common.date') }}</th>
                <th>{{ __('common.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appeals as $appeal)
                <tr>
                    <td>{{ $appeal->user?->fullNameAr() ?: '—' }}</td>
                    <td>{{ $appeal->subject }}</td>
                    <td><span class="chip {{ $appeal->status->chipClass() }}">{{ $appeal->status->label() }}</span></td>
                    <td class="num">{{ $appeal->created_at->format('Y-m-d') }}</td>
                    <td>
                        <a href="{{ route('admin.appeals.index') }}" class="chip chip-info" style="text-decoration:none">{{ __('common.view') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-6">{{ __('admin.auctions.none_appeals') }}</td></tr>
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.card>

@endsection
