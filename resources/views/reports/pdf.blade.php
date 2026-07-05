{{--
    Transient financial-report PDF (rendered by mpdf via
    InteractsWithFinancialReports::renderReportPdf). Self-contained HTML with
    inline CSS only — no flexbox (mpdf doesn't support it). Money uses dzd_pdf()
    so the amount stays LTR-coherent inside the RTL document.

    Expects: $report, $filters (ReportFilters), $showUser (bool), $scopeLabel.
--}}
@php
    $s = $report['summary'];
    $isCitizen = ! $showUser;
    $dir = locale_is_rtl() ? 'rtl' : 'ltr';
    $end = $dir === 'rtl' ? 'left' : 'right';
    $rows = [
        [$isCitizen ? __('reports.kpi_net_spent') : __('reports.kpi_net_revenue'), $s['net_revenue']],
        [__('reports.kpi_gross_confirmed'), $s['gross_confirmed']],
        [__('reports.kpi_final_payments'), $s['final_payments']],
        [__('reports.kpi_deposits_held'), $s['deposits_held']],
        [__('reports.kpi_book_sales'), $s['book_sales']],
        [__('reports.kpi_refunded'), $s['refunded']],
        [__('reports.kpi_forfeited'), $s['forfeited']],
        [__('reports.kpi_pending'), $s['pending']],
    ];
@endphp
<html dir="{{ $dir }}" lang="{{ locale_lang() }}">
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; color: #1f2937; font-size: 12px; }
    h1 { font-size: 18px; margin: 0 0 4px; color: #1B4D3E; }
    .meta { color: #6b7280; font-size: 11px; margin: 0 0 2px; }
    h2 { font-size: 13px; margin: 18px 0 6px; color: #1B4D3E; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: start; }
    th { background: #f3f4f6; font-size: 11px; }
    td.amt { text-align: {{ $end }}; white-space: nowrap; }
    tr.total td { font-weight: bold; border-top: 2px solid #9ca3af; }
</style>
</head>
<body>
    <h1>{{ __('reports.pdf_title') }} — {{ __('common.app_name') }}</h1>
    <p class="meta">{{ __('reports.pdf_scope') }}: {{ $scopeLabel }}</p>
    <p class="meta">{{ __('reports.pdf_range') }}: {{ $filters->rangeLabel() }}</p>
    <p class="meta">{{ __('reports.pdf_generated_at') }}: {{ now()->format('Y-m-d H:i') }}</p>
    <p class="meta">{{ __('reports.kpi_transactions') }}: {{ number_format($s['txn_count']) }}</p>

    <h2>{{ __('reports.section_summary') }}</h2>
    <table>
        @foreach($rows as $row)
        <tr>
            <td>{{ $row[0] }}</td>
            <td class="amt">{!! dzd_pdf($row[1]) !!}</td>
        </tr>
        @endforeach
    </table>

    @if($report['by_status']->isNotEmpty())
    <h2>{{ __('reports.chart_by_status') }}</h2>
    <table>
        <thead><tr><th>{{ __('reports.th_status') }}</th><th>{{ __('reports.kpi_transactions') }}</th><th class="amt">{{ __('reports.th_amount') }}</th></tr></thead>
        <tbody>
        @foreach($report['by_status'] as $row)
            <tr><td>{{ $row['label'] }}</td><td>{{ number_format($row['cnt']) }}</td><td class="amt">{!! dzd_pdf($row['total']) !!}</td></tr>
        @endforeach
        </tbody>
    </table>
    @endif

    @if($report['by_category']->isNotEmpty())
    <h2>{{ __('reports.chart_by_category') }}</h2>
    <table>
        <thead><tr><th>{{ __('reports.f_category') }}</th><th class="amt">{{ __('reports.th_amount') }}</th></tr></thead>
        <tbody>
        @foreach($report['by_category'] as $row)
            <tr><td>{{ $row['name'] }}</td><td class="amt">{!! dzd_pdf($row['total']) !!}</td></tr>
        @endforeach
        </tbody>
    </table>
    @endif

    @if(($report['by_entity'] ?? collect())->isNotEmpty())
    <h2>{{ __('reports.chart_by_entity') }}</h2>
    <table>
        <thead><tr><th>{{ __('reports.f_entity') }}</th><th class="amt">{{ __('reports.th_amount') }}</th></tr></thead>
        <tbody>
        @foreach($report['by_entity'] as $row)
            <tr><td>{{ $row['name'] }}</td><td class="amt">{!! dzd_pdf($row['total']) !!}</td></tr>
        @endforeach
        </tbody>
    </table>
    @endif

    @if($report['fees'])
    @php $fees = $report['fees']; @endphp
    <h2>{{ __('reports.section_fees') }} ({{ __('reports.fee_awarded_count') }}: {{ number_format($fees['_count']) }})</h2>
    <table>
        <tr><td>{{ __('fees.line.hammer_price') }}</td><td class="amt">{!! dzd_pdf($fees['hammer_price']) !!}</td></tr>
        <tr><td>{{ __('fees.line.appraisal_fee') }}</td><td class="amt">{!! dzd_pdf($fees['appraisal_fee']) !!}</td></tr>
        @if($fees['hammer_fee'] > 0)
        <tr><td>{{ __('fees.line.hammer_fee') }}</td><td class="amt">{!! dzd_pdf($fees['hammer_fee']) !!}</td></tr>
        @endif
        <tr><td>{{ __('fees.line.proportional_buyer') }}</td><td class="amt">{!! dzd_pdf($fees['proportional_buyer']) !!}</td></tr>
        <tr><td>{{ __('fees.line.work_session') }}</td><td class="amt">{!! dzd_pdf($fees['work_session_fee']) !!}</td></tr>
        <tr><td>{{ __('fees.line.tva') }}</td><td class="amt">{!! dzd_pdf($fees['tva']) !!}</td></tr>
        <tr class="total"><td>{{ __('fees.line.buyer_total') }}</td><td class="amt">{!! dzd_pdf($fees['buyer_total']) !!}</td></tr>
    </table>
    @endif
</body>
</html>
