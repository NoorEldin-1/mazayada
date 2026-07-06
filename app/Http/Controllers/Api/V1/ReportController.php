<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentType;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Concerns\InteractsWithFinancialReports;
use App\Http\Resources\Api\V1\TransactionResource;
use App\Models\Document;
use App\Models\Payment;
use App\Services\FinancialReportService;
use App\Support\ReportFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * @authenticated
     */
    public function summary(Request $request, FinancialReportService $service): JsonResponse
    {
        $filters = ReportFilters::fromRequest($request);

        $report = $service->build(
            fn () => $filters->applyTo($this->basePayments()),
            $filters,
            [
                'dimensions' => ['category' => true, 'wilaya' => false, 'entity' => false],
                'awards' => $filters->applyToDocuments($this->baseAwards()),
            ]
        );

        return $this->ok($report);
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
