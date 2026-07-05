<?php

namespace App\Http\Controllers\Citizen;

use App\Enums\DocumentType;
use App\Http\Controllers\Concerns\InteractsWithFinancialReports;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Document;
use App\Models\Payment;
use App\Services\FinancialReportService;
use App\Support\ReportFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Personal Financial Report for the citizen dashboard (تقاريري المالية).
 *
 * Strictly scoped to the signed-in user's own payments (where user_id) — the
 * same self-service model as the rest of the citizen dashboard, so no extra
 * permission gate is required (any authenticated user sees only their own data).
 */
class ReportController extends Controller
{
    use InteractsWithFinancialReports;

    public function index(Request $request, FinancialReportService $service): View
    {
        $filters = ReportFilters::fromRequest($request);

        $report = $service->build(
            fn () => $filters->applyTo($this->basePayments()),
            $filters,
            [
                'dimensions' => ['category' => true, 'wilaya' => false, 'entity' => false],
                'awards' => $filters->applyToDocuments($this->baseAwards()),
            ],
        );

        $transactions = $filters->applyTo($this->basePayments())
            ->with(['auction'])
            ->orderByDesc('payments.created_at')
            ->paginate(20)
            ->withQueryString();

        return view('citizen.reports', [
            'report' => $report,
            'filters' => $filters,
            'transactions' => $transactions,
            'isPlatform' => false,
            'showUser' => false,
            'scope' => 'citizen',
            'categories' => Category::where('is_active', true)->get(),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filters = ReportFilters::fromRequest($request);

        return $this->streamReportCsv(
            $filters->applyTo($this->basePayments()),
            showUser: false,
            filename: 'my-financial-report-'.now()->format('Ymd-His').'.csv',
        );
    }

    public function exportPdf(Request $request, FinancialReportService $service): Response
    {
        $filters = ReportFilters::fromRequest($request);

        $report = $service->build(
            fn () => $filters->applyTo($this->basePayments()),
            $filters,
            [
                'dimensions' => ['category' => true, 'wilaya' => false, 'entity' => false],
                'awards' => $filters->applyToDocuments($this->baseAwards()),
            ],
        );

        return $this->renderReportPdf('reports.pdf', [
            'report' => $report,
            'filters' => $filters,
            'showUser' => false,
            'scopeLabel' => auth()->user()->name,
        ], 'my-financial-report-'.now()->format('Ymd-His').'.pdf');
    }

    private function basePayments(): Builder
    {
        return Payment::query()->where('payments.user_id', auth()->id());
    }

    private function baseAwards(): Builder
    {
        return Document::query()
            ->where('type', DocumentType::AWARD)
            ->where('user_id', auth()->id());
    }
}
