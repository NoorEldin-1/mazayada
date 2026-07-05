<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentType;
use App\Http\Controllers\Concerns\InteractsWithFinancialReports;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Document;
use App\Models\Entity;
use App\Models\Payment;
use App\Models\Wilaya;
use App\Services\FinancialReportService;
use App\Support\ReportFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Financial Reports for the admin area (التقارير المالية).
 *
 * Platform staff (entity_id === null / SUPER_ADMIN) see every auction's money;
 * entity staff/viewers are isolated to their own entity automatically — every
 * figure is built from Payment::whereHas('auction'), so the Auction EntityScope
 * (active on admin.* routes) filters the sub-query. No manual entity_id checks.
 */
class AdminReportController extends Controller
{
    use InteractsWithFinancialReports;

    public function index(Request $request, FinancialReportService $service): View
    {
        $this->authorize('reports.view');

        $filters = ReportFilters::fromRequest($request);
        $isPlatform = auth()->user()->entity_id === null;

        $report = $this->buildReport($service, $filters, $isPlatform);

        $transactions = $filters->applyTo($this->basePayments())
            ->with(['auction', 'user'])
            ->orderByDesc('payments.created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.index', [
            'report' => $report,
            'filters' => $filters,
            'transactions' => $transactions,
            'isPlatform' => $isPlatform,
            'showUser' => true,
            'scope' => 'admin',
            'categories' => Category::where('is_active', true)->get(),
            'wilayas' => $isPlatform ? Wilaya::orderBy('code')->get() : collect(),
            'entities' => $isPlatform ? Entity::where('is_active', true)->orderBy('name_ar')->get() : collect(),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('reports.export');

        $filters = ReportFilters::fromRequest($request);

        AuditLog::log('FINANCIAL_REPORT_EXPORTED', 'Report', 'admin-financial-report', null, null, ['format' => 'csv']);

        return $this->streamReportCsv(
            $filters->applyTo($this->basePayments()),
            showUser: true,
            filename: 'financial-report-'.now()->format('Ymd-His').'.csv',
        );
    }

    public function exportPdf(Request $request, FinancialReportService $service): Response
    {
        $this->authorize('reports.export');

        $filters = ReportFilters::fromRequest($request);
        $isPlatform = auth()->user()->entity_id === null;

        AuditLog::log('FINANCIAL_REPORT_EXPORTED', 'Report', 'admin-financial-report', null, null, ['format' => 'pdf']);

        return $this->renderReportPdf('reports.pdf', [
            'report' => $this->buildReport($service, $filters, $isPlatform),
            'filters' => $filters,
            'showUser' => true,
            'scopeLabel' => $isPlatform ? __('reports.scope_platform') : (auth()->user()->entity?->name ?? '—'),
        ], 'financial-report-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReport(FinancialReportService $service, ReportFilters $filters, bool $isPlatform): array
    {
        return $service->build(
            fn () => $filters->applyTo($this->basePayments()),
            $filters,
            [
                'dimensions' => [
                    'category' => true,
                    'wilaya' => $isPlatform,
                    'entity' => $isPlatform,
                ],
                'awards' => $filters->applyToDocuments($this->baseAwards()),
            ],
        );
    }

    /**
     * Payments in the admin scope. whereHas('auction') both excludes orphan
     * (auction-less) payments AND triggers the Auction EntityScope, so entity
     * accounts only ever aggregate their own auctions' money.
     */
    private function basePayments(): Builder
    {
        return Payment::query()->whereHas('auction');
    }

    private function baseAwards(): Builder
    {
        return Document::query()->where('type', DocumentType::AWARD)->whereHas('auction');
    }
}
