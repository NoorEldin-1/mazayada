{{--
    Analytics for the Financial Reports module — ApexCharts via the data-chart
    contract (resources/js/dashboard.js), zero inline JS. Money series are
    converted to whole dinars so axes/tooltips stay readable; the currency is
    noted in each card title.

    Expects: $report (array of collections/arrays), $scope.
--}}
@php
    $toDinars = fn ($centimes) => intdiv((int) $centimes, 100);
    $cur = __('common.currency');
    $series = $report['series'];
    $hasAny = ! empty($series['categories'])
        || $report['by_type']->isNotEmpty()
        || $report['by_status']->isNotEmpty()
        || $report['by_category']->isNotEmpty()
        || ($report['by_wilaya'] ?? collect())->isNotEmpty()
        || ($report['by_entity'] ?? collect())->isNotEmpty();
@endphp

@if($hasAny)
<h2 class="text-base font-bold text-ink mb-3">{{ __('reports.section_analytics') }}</h2>
@endif

{{-- Revenue over time --}}
@if(! empty($series['categories']))
@php
    $revChart = [
        'categories' => $series['categories'],
        'series' => [['name' => __('reports.series_revenue'), 'data' => array_map($toDinars, $series['data'])]],
    ];
@endphp
<x-ui.card :title="__('reports.chart_revenue_over_time').' ('.$cur.')'" class="mb-5">
    <div data-chart data-chart-type="area" data-chart-height="300">
        <div data-chart-target></div>
        <script type="application/json">@json($revChart)</script>
    </div>
</x-ui.card>
@endif

{{-- Two donuts: by payment type + by status --}}
@if($report['by_type']->isNotEmpty() || $report['by_status']->isNotEmpty())
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    @if($report['by_type']->isNotEmpty())
    @php
        $typeChart = [
            'labels' => $report['by_type']->pluck('label')->all(),
            'series' => $report['by_type']->map(fn ($r) => $toDinars($r['total']))->all(),
        ];
    @endphp
    <x-ui.card :title="__('reports.chart_by_type').' ('.$cur.')'">
        <div data-chart data-chart-type="donut" data-chart-height="300">
            <div data-chart-target></div>
            <script type="application/json">@json($typeChart)</script>
        </div>
    </x-ui.card>
    @endif

    @if($report['by_status']->isNotEmpty())
    @php
        $statusChart = [
            'labels' => $report['by_status']->pluck('label')->all(),
            'series' => $report['by_status']->map(fn ($r) => $toDinars($r['total']))->all(),
        ];
    @endphp
    <x-ui.card :title="__('reports.chart_by_status').' ('.$cur.')'">
        <div data-chart data-chart-type="donut" data-chart-height="300">
            <div data-chart-target></div>
            <script type="application/json">@json($statusChart)</script>
        </div>
    </x-ui.card>
    @endif
</div>
@endif

{{-- Confirmed collections by category --}}
@if($report['by_category']->isNotEmpty())
@php
    $categoryChart = [
        'categories' => $report['by_category']->pluck('name')->all(),
        'series' => [['name' => __('reports.series_revenue'), 'data' => $report['by_category']->map(fn ($r) => $toDinars($r['total']))->all()]],
    ];
@endphp
<x-ui.card :title="__('reports.chart_by_category').' ('.$cur.')'" class="mb-5">
    <div data-chart data-chart-type="bar" data-chart-horizontal="true" data-chart-height="{{ max(240, $report['by_category']->count() * 46) }}">
        <div data-chart-target></div>
        <script type="application/json">@json($categoryChart)</script>
    </div>
</x-ui.card>
@endif

{{-- Confirmed collections by wilaya (platform admin only) --}}
@if(($report['by_wilaya'] ?? collect())->isNotEmpty())
@php
    $wilayaChart = [
        'categories' => $report['by_wilaya']->pluck('name')->all(),
        'series' => [['name' => __('reports.series_revenue'), 'data' => $report['by_wilaya']->map(fn ($r) => $toDinars($r['total']))->all()]],
    ];
@endphp
<x-ui.card :title="__('reports.chart_by_wilaya').' ('.$cur.')'" class="mb-5">
    <div data-chart data-chart-type="bar" data-chart-horizontal="true" data-chart-height="{{ max(240, $report['by_wilaya']->count() * 46) }}">
        <div data-chart-target></div>
        <script type="application/json">@json($wilayaChart)</script>
    </div>
</x-ui.card>
@endif

{{-- Confirmed collections by entity (platform admin only) --}}
@if(($report['by_entity'] ?? collect())->isNotEmpty())
@php
    $entityChart = [
        'categories' => $report['by_entity']->pluck('name')->all(),
        'series' => [['name' => __('reports.series_revenue'), 'data' => $report['by_entity']->map(fn ($r) => $toDinars($r['total']))->all()]],
    ];
@endphp
<x-ui.card :title="__('reports.chart_by_entity').' ('.$cur.')'" class="mb-5">
    <div data-chart data-chart-type="bar" data-chart-horizontal="true" data-chart-height="{{ max(240, $report['by_entity']->count() * 46) }}">
        <div data-chart-target></div>
        <script type="application/json">@json($entityChart)</script>
    </div>
</x-ui.card>
@endif

{{-- Fee breakdown (awarded auctions) --}}
@if($report['fees'])
@php $fees = $report['fees']; @endphp
<x-ui.card class="mb-5">
    <x-slot:header>
        <h3 class="text-base font-semibold text-ink">{{ __('reports.section_fees') }}</h3>
        <span class="ms-auto chip chip-muted">{{ __('reports.fee_awarded_count') }}: {{ number_format($fees['_count']) }}</span>
    </x-slot:header>
    <p class="text-xs text-muted mb-4">{{ __('reports.fees_note') }}</p>
    <div class="overflow-x-auto">
        <table class="ui-table" style="min-width:420px">
            <tbody>
                <tr><td>{{ __('fees.line.hammer_price') }}</td><td class="num text-end">{!! dzd_html($fees['hammer_price']) !!}</td></tr>
                <tr><td>{{ __('fees.line.appraisal_fee') }}</td><td class="num text-end">{!! dzd_html($fees['appraisal_fee']) !!}</td></tr>
                @if($fees['hammer_fee'] > 0)
                <tr><td>{{ __('fees.line.hammer_fee') }}</td><td class="num text-end">{!! dzd_html($fees['hammer_fee']) !!}</td></tr>
                @endif
                <tr><td>{{ __('fees.line.proportional_buyer') }}</td><td class="num text-end">{!! dzd_html($fees['proportional_buyer']) !!}</td></tr>
                <tr><td>{{ __('fees.line.work_session') }}</td><td class="num text-end">{!! dzd_html($fees['work_session_fee']) !!}</td></tr>
                <tr><td>{{ __('fees.line.tva') }}</td><td class="num text-end">{!! dzd_html($fees['tva']) !!}</td></tr>
                <tr class="font-bold text-ink"><td>{{ __('fees.line.buyer_total') }}</td><td class="num text-end">{!! dzd_html($fees['buyer_total']) !!}</td></tr>
            </tbody>
        </table>
    </div>
</x-ui.card>
@endif
