@extends('documents._layout')

@section('doc-content')
    {{-- تقرير المزاد — a full, signed snapshot of every auction detail as of issue
         time. Laid out as labelled key/value + data tables so nothing is implied. --}}

    {{-- ===== 1. Auction identity ===== --}}
    <div class="section">
        <h3>{{ __('auction_reports.sec_identity') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('auction_reports.f_title_ar') }}</td><td>{{ $auction->title_ar ?: '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_title_fr') }}</td><td>{{ $auction->title_fr ?: '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_title_en') }}</td><td>{{ $auction->title_en ?: '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_id') }}</td><td style="direction:ltr">{{ $auction->id }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_entity') }}</td><td>{{ $auction->entity?->name ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_category') }}</td><td>{{ $auction->category?->name ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_type') }}</td><td>{{ $auction->auction_type?->label() ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_asset_class') }}</td><td>{{ $auction->asset_class?->label() ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_condition') }}</td><td>{{ $auction->condition?->label() ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_unit_count') }}</td><td class="num">{{ $auction->unit_count ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_created_by') }}</td><td>{{ $auction->createdByUser?->fullNameAr() ?? '—' }}</td></tr>
        </table>
    </div>

    {{-- ===== 2. Lifecycle & timeline ===== --}}
    <div class="section">
        <h3>{{ __('auction_reports.sec_lifecycle') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('auction_reports.f_status') }}</td><td>{{ $auction->status->label() }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_start') }}</td><td>{{ optional($auction->start_time)->format('Y-m-d H:i') ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_end') }}</td><td>{{ optional($auction->end_time)->format('Y-m-d H:i') ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_extensions') }}</td><td class="num">{{ (int) $auction->extension_count }} / {{ (int) $auction->max_extensions }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_closed_at') }}</td><td>{{ optional($auction->closed_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_settled_at') }}</td><td>{{ optional($auction->settled_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_inspection') }}</td><td>{{ optional($auction->inspection_start)->format('Y-m-d H:i') ?? '—' }} — {{ optional($auction->inspection_end)->format('Y-m-d H:i') ?? '—' }}</td></tr>
        </table>
    </div>

    {{-- ===== 3. Financials + fee breakdown ===== --}}
    <div class="section">
        <h3>{{ __('auction_reports.sec_financials') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('auction_reports.f_opening_price') }}</td><td style="text-align:end">{!! dzd_pdf((int) $auction->opening_price) !!}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_deposit') }}</td><td style="text-align:end">{!! dzd_pdf((int) $auction->deposit_amount) !!}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_entry_fee') }}</td><td style="text-align:end">{!! dzd_pdf((int) $auction->entry_fee) !!}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_book_price') }}</td><td style="text-align:end">{!! dzd_pdf((int) $auction->book_price) !!}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_current_price') }}</td><td style="text-align:end">{!! dzd_pdf($auction->currentPrice()) !!}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_final_price') }}</td><td style="text-align:end">{{ $auction->final_price !== null ? dzd_pdf((int) $auction->final_price) : '—' }}</td></tr>
        </table>

        <p style="font-size:10px;color:#6b7280;margin:8px 0 3px">{{ __('auction_reports.fees_note') }}</p>
        <table class="fees">
            @foreach($fees->lines() as $line)
                <tr class="{{ $line['key'] === 'fees.line.buyer_total' ? 'total' : '' }}">
                    <td>{{ __($line['key']) }}</td>
                    <td style="text-align:end">{!! dzd_pdf($line['amount']) !!}</td>
                </tr>
            @endforeach
            @if($fees->customsImmediateDue !== null)
                <tr><td>{{ __('fees.line.customs_immediate') }}</td><td style="text-align:end">{!! dzd_pdf($fees->customsImmediateDue) !!}</td></tr>
            @endif
        </table>
    </div>

    {{-- ===== 4. Bidding summary ===== --}}
    <div class="section">
        <h3>{{ __('auction_reports.sec_bidding') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('auction_reports.f_bid_count') }}</td><td class="num">{{ $auction->bidCount() }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_participants') }}</td><td class="num">{{ $auction->participants()->count() }}</td></tr>
        </table>

        <table class="fees" style="margin-top:8px">
            <tr>
                <th style="width:12%">#</th>
                <th>{{ __('auction_reports.th_bidder') }}</th>
                <th style="text-align:end">{{ __('auction_reports.th_amount') }}</th>
                <th style="text-align:end">{{ __('auction_reports.th_time') }}</th>
            </tr>
            @forelse($bids as $i => $bid)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $bid->bidderAlias() }}</td>
                    <td style="text-align:end">{!! dzd_pdf((int) $bid->amount) !!}</td>
                    <td style="text-align:end">{{ optional($bid->bid_time)->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;color:#9ca3af">{{ __('auction_reports.no_bids') }}</td></tr>
            @endforelse
        </table>
    </div>

    {{-- ===== 5. Winner ===== --}}
    @if($auction->winner)
    <div class="section">
        <h3>{{ __('auction_reports.sec_winner') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('auction_reports.f_winner_name') }}</td><td>{{ $auction->winner->fullNameAr() }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_winner_nin') }}</td><td style="direction:ltr">{{ mask_nin($auction->winner->nin) }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_winner_phone') }}</td><td style="direction:ltr">{{ $auction->winner->phone ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_final_price') }}</td><td style="text-align:end">{{ $auction->final_price !== null ? dzd_pdf((int) $auction->final_price) : '—' }}</td></tr>
        </table>
    </div>
    @endif

    {{-- ===== 6. Payments ledger ===== --}}
    <div class="section">
        <h3>{{ __('auction_reports.sec_payments') }}</h3>
        <table class="fees">
            <tr>
                <th>{{ __('auction_reports.th_pay_type') }}</th>
                <th>{{ __('auction_reports.th_payer') }}</th>
                <th style="text-align:end">{{ __('auction_reports.th_amount') }}</th>
                <th>{{ __('auction_reports.th_status') }}</th>
                <th style="text-align:end">{{ __('auction_reports.th_date') }}</th>
            </tr>
            @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment->payment_type->label() }}</td>
                    <td>{{ $payment->user?->fullNameAr() ?? '—' }}</td>
                    <td style="text-align:end">{!! dzd_pdf((int) $payment->amount) !!}</td>
                    <td>{{ $payment->status->label() }}</td>
                    <td style="text-align:end">{{ $payment->created_at?->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:#9ca3af">{{ __('auction_reports.no_payments') }}</td></tr>
            @endforelse
        </table>
    </div>

    {{-- ===== 7. Documents issued ===== --}}
    <div class="section">
        <h3>{{ __('auction_reports.sec_documents') }}</h3>
        <table class="fees">
            <tr>
                <th>{{ __('auction_reports.th_doc_type') }}</th>
                <th>{{ __('auction_reports.th_doc_title') }}</th>
                <th style="text-align:end">{{ __('auction_reports.th_date') }}</th>
            </tr>
            @forelse($documents as $doc)
                <tr>
                    <td>{{ $doc->type->label() }}</td>
                    <td>{{ $doc->title }}</td>
                    <td style="text-align:end">{{ $doc->created_at?->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center;color:#9ca3af">{{ __('auction_reports.no_documents') }}</td></tr>
            @endforelse
        </table>
    </div>

    {{-- ===== 8. Appeals ===== --}}
    <div class="section">
        <h3>{{ __('auction_reports.sec_appeals') }}</h3>
        @if($appeals->isEmpty())
            <p style="font-size:11px;color:#9ca3af">{{ __('auction_reports.no_appeals') }}</p>
        @else
            <table class="fees">
                <tr>
                    <th>{{ __('auction_reports.th_subject') }}</th>
                    <th>{{ __('auction_reports.th_status') }}</th>
                    <th style="text-align:end">{{ __('auction_reports.th_date') }}</th>
                </tr>
                @foreach($appeals as $appeal)
                    <tr>
                        <td>{{ $appeal->subject }}</td>
                        <td>{{ $appeal->status->label() }}</td>
                        <td style="text-align:end">{{ $appeal->created_at?->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    {{-- ===== 9. Delivery ===== --}}
    <div class="section">
        <h3>{{ __('auction_reports.sec_delivery') }}</h3>
        @if($auction->delivery)
            <table class="kv">
                <tr><td class="k">{{ __('auction_reports.f_delivery_status') }}</td><td>{{ $auction->delivery->status?->label() ?? $auction->delivery->status ?? '—' }}</td></tr>
                <tr><td class="k">{{ __('auction_reports.f_delivery_date') }}</td><td>{{ optional($auction->delivery->delivered_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
            </table>
        @else
            <p style="font-size:11px;color:#9ca3af">{{ __('auction_reports.no_delivery') }}</p>
        @endif
    </div>

    {{-- ===== 10. Asset location ===== --}}
    <div class="section">
        <h3>{{ __('auction_reports.sec_location') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('auction_reports.f_location') }}</td><td>{{ $auction->asset_location ?: '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_wilaya') }}</td><td>{{ $auction->wilaya?->name ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_commune') }}</td><td>{{ $auction->commune?->name ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_coords') }}</td><td style="direction:ltr">{{ $auction->latitude !== null ? $auction->latitude.', '.$auction->longitude : '—' }}</td></tr>
        </table>
    </div>

    {{-- ===== 11. Specifications ===== --}}
    @php $specs = $auction->localizedSpecifications(); @endphp
    @if(!empty($specs))
    <div class="section">
        <h3>{{ __('auction_reports.sec_specs') }}</h3>
        <table class="kv">
            @foreach($specs as $spec)
                <tr><td class="k">{{ $spec['title'] }}</td><td>{{ $spec['body'] }}</td></tr>
            @endforeach
        </table>
    </div>
    @endif

    {{-- ===== 12. Issue metadata ===== --}}
    <div class="section">
        <h3>{{ __('auction_reports.sec_issue') }}</h3>
        <table class="kv">
            <tr><td class="k">{{ __('auction_reports.f_sequence') }}</td><td class="num">{{ $sequenceNo }}</td></tr>
            <tr><td class="k">{{ __('auction_reports.f_issued_at') }}</td><td>{{ $issuedAt->format('Y-m-d H:i') }}</td></tr>
        </table>
    </div>

    <div class="legal">{{ __('auction_reports.legal_notice') }}</div>
@endsection
