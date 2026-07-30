<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentType;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Concerns\InteractsWithFinancialReports;
use App\Http\Resources\Api\V1\TransactionResource;
use App\Models\Document;
use App\Models\Payment;
use App\Services\FinancialReportService;
use App\Support\Api\ReportMoneyFormatter;
use App\Support\ReportFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Financial Reports
 *
 * The citizen's personal financial report (تقاريري المالية).
 */
class ReportController extends ApiController
{
    use InteractsWithFinancialReports;

    /**
     * Report summary
     *
     * Aggregated KPIs, dimensions, and monthly series for the authenticated user's payments.
     *
     * Every monetary figure is `{ amount, formatted }` in DINARS — the same unit
     * as `/reports/transactions` and the rest of the API. (`series.data` stays a
     * flat integer array of dinars so it can be plotted directly.)
     *
     * @authenticated
     */
    public function summary(Request $request, FinancialReportService $service): JsonResponse
    {
        $filters = ReportFilters::fromRequest($request);

        return $this->ok(ReportMoneyFormatter::format($this->buildReport($filters, $service)));
    }

    /**
     * Financial transactions
     *
     * Paginated list of the user's individual payments, filterable by the same parameters.
     *
     * @authenticated
     */
    public function transactions(Request $request): JsonResponse
    {
        $filters = ReportFilters::fromRequest($request);

        $transactions = $filters->applyTo($this->basePayments())
            ->with(['auction.category', 'auction.wilaya'])
            ->orderByDesc('payments.created_at')
            ->paginate(20)
            ->withQueryString();

        return $this->paginated($transactions, TransactionResource::class);
    }

    /**
     * Export transactions
     *
     * Downloads the filtered transactions as a file, using the same filters as
     * `/reports/summary` and `/reports/transactions`. `csv` streams (chunked, so a
     * long history is safe); `pdf` renders the full report through mpdf with the
     * Arabic shaping/RTL of the web export.
     *
     * The response is a BINARY download (`text/csv` or `application/pdf`), not the
     * JSON envelope — request it with a bytes/stream response type and save it,
     * don't try to decode it. Send the bearer token as usual.
     *
     * @urlParam format string required csv or pdf. Example: csv
     *
     * @response 200 The file body (Content-Disposition: attachment).
     */
    public function export(Request $request, string $format, FinancialReportService $service): Response|StreamedResponse|JsonResponse
    {
        if (! in_array($format, ['csv', 'pdf'], true)) {
            return $this->fail(__('common.api.not_found'), [], 404);
        }

        $filters = ReportFilters::fromRequest($request);
        $stamp = now()->format('Ymd-His');

        if ($format === 'csv') {
            return $this->streamReportCsv(
                $filters->applyTo($this->basePayments()),
                showUser: false,
                filename: "my-financial-report-{$stamp}.csv",
            );
        }

        return $this->renderReportPdf('reports.pdf', [
            'report' => $this->buildReport($filters, $service),
            'filters' => $filters,
            'showUser' => false,
            'scopeLabel' => $request->user()->name,
        ], "my-financial-report-{$stamp}.pdf");
    }

    /**
     * The citizen-scoped report. Shared by summary() and the PDF export so the
     * downloaded figures always match the ones on screen.
     *
     * @return array<string, mixed>  money in CENTIMES (the service's unit)
     */
    private function buildReport(ReportFilters $filters, FinancialReportService $service): array
    {
        return $service->build(
            fn () => $filters->applyTo($this->basePayments()),
            $filters,
            [
                'dimensions' => ['category' => true, 'wilaya' => false, 'entity' => false],
                'awards' => $filters->applyToDocuments($this->baseAwards()),
            ]
        );
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
