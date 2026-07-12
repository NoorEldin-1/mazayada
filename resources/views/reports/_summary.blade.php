{{--
    Financial report KPI tiles. Shared by the admin, entity and citizen reports.
    Money values are centimes → rendered via <x-ui.stat-tile money>. The hero tile
    is framed as "net revenue" for admin/entity and "net spent" for a citizen.

    Expects: $report (array), $scope ('admin'|'citizen').
--}}
@php
    $s = $report['summary'];
    $isCitizen = ($scope ?? '') === 'citizen';
    $heroLabel = $isCitizen ? __('reports.kpi_net_spent') : __('reports.kpi_net_revenue');
    $heroHint = $isCitizen ? __('reports.kpi_net_spent_hint') : __('reports.kpi_net_revenue_hint');
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
    <x-ui.stat-tile layout="stacked" tone="gold" :label="$heroLabel" :value="$s['net_revenue']" :hint="$heroHint" money>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile layout="stacked" tone="blue" :label="__('reports.kpi_gross_confirmed')" :value="$s['gross_confirmed']" :hint="__('reports.kpi_gross_confirmed_hint')" money>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile layout="stacked" tone="violet" :label="__('reports.kpi_deposits_held')" :value="$s['deposits_held']" :hint="__('reports.kpi_deposits_held_hint')" money>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile layout="stacked" tone="mint" :label="__('reports.kpi_transactions')" :value="number_format($s['txn_count'])" :hint="__('reports.kpi_avg_transaction').': '.dzd($s['avg_txn'])">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
    </x-ui.stat-tile>

    <x-ui.stat-tile layout="stacked" tone="mint" :label="__('reports.kpi_final_payments')" :value="$s['final_payments']" money>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile layout="stacked" tone="ok" :label="__('reports.kpi_book_sales')" :value="$s['book_sales']" money>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile layout="stacked" tone="blue" :label="__('reports.kpi_refunded')" :value="$s['refunded']" money>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
    </x-ui.stat-tile>
    <x-ui.stat-tile layout="stacked" tone="danger" :label="__('reports.kpi_forfeited')" :value="$s['forfeited']" money>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    </x-ui.stat-tile>
</div>
