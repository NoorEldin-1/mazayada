<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Wilaya;
use App\Support\ReportFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Aggregates the Financial Reports figures for a scoped, filtered payments query.
 *
 * The caller passes a `$payments` factory that returns a FRESH, already
 * scope-narrowed + filter-applied Payment builder on every call (citizen:
 * where user_id; entity/admin: whereHas('auction') so EntityScope isolates).
 * A factory — not a single shared builder — keeps every aggregate independent
 * and sidesteps Eloquent builder-clone footguns.
 *
 * Money is integer centimes throughout. Statuses drive the meaning:
 *   - net revenue  = confirmed FINAL_PAYMENT + BOOK_PURCHASE + ENTRY_FEE + FORFEITED
 *   - deposits held= confirmed DEPOSIT (refundable — NOT revenue)
 *   - refunds/forfeits mutate the deposit row's status, so figures are
 *     status-aware sums, never a naive SUM(amount).
 */
class FinancialReportService
{
    /**
     * @param  callable():Builder  $payments  returns a fresh filtered Payment builder
     * @param  array{dimensions?: array<string, bool>, awards?: Builder|null}  $options
     * @return array<string, mixed>
     */
    public function build(callable $payments, ReportFilters $filters, array $options = []): array
    {
        $dimensions = $options['dimensions'] ?? [];

        return [
            'summary' => $this->summary($payments()),
            'by_type' => $this->groupByType($payments()),
            'by_status' => $this->groupByStatus($payments()),
            'series' => $this->monthlyRevenue($payments()),
            'by_category' => ($dimensions['category'] ?? true) ? $this->groupByAuctionColumn($payments(), 'category_id', Category::class) : collect(),
            'by_wilaya' => ($dimensions['wilaya'] ?? false) ? $this->groupByAuctionColumn($payments(), 'wilaya_id', Wilaya::class) : collect(),
            'by_entity' => ($dimensions['entity'] ?? false) ? $this->groupByAuctionColumn($payments(), 'entity_id', Entity::class) : collect(),
            'fees' => $this->feeBreakdown($options['awards'] ?? null),
        ];
    }

    /**
     * KPI aggregates from a single grouped (type, status) query.
     *
     * @return array<string, int>
     */
    public function summary(Builder $query): array
    {
        $rows = $query
            ->select('payment_type', 'status')
            ->selectRaw('COUNT(*) as cnt')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('payment_type', 'status')
            ->get();

        $confirmed = 0;
        $finalPayments = 0;
        $bookSales = 0;
        $entryFees = 0;
        $depositsHeld = 0;
        $refunded = 0;
        $forfeited = 0;
        $pending = 0;
        $failedCount = 0;
        $txnCount = 0;
        $txnTotal = 0;

        foreach ($rows as $row) {
            $type = $row->payment_type?->value;
            $status = $row->status?->value;
            $total = (int) $row->total;
            $cnt = (int) $row->cnt;

            $txnCount += $cnt;
            $txnTotal += $total;

            if ($status === PaymentStatus::CONFIRMED->value) {
                $confirmed += $total;
                match ($type) {
                    'FINAL_PAYMENT' => $finalPayments += $total,
                    'BOOK_PURCHASE' => $bookSales += $total,
                    'ENTRY_FEE' => $entryFees += $total,
                    'DEPOSIT' => $depositsHeld += $total,
                    default => null,
                };
            } elseif ($status === PaymentStatus::REFUNDED->value) {
                $refunded += $total;
            } elseif ($status === PaymentStatus::FORFEITED->value) {
                $forfeited += $total;
            } elseif ($status === PaymentStatus::PENDING->value) {
                $pending += $total;
            } elseif ($status === PaymentStatus::FAILED->value) {
                $failedCount += $cnt;
            }
        }

        return [
            'net_revenue' => $finalPayments + $bookSales + $entryFees + $forfeited,
            'gross_confirmed' => $confirmed,
            'final_payments' => $finalPayments,
            'deposits_held' => $depositsHeld,
            'book_sales' => $bookSales,
            'refunded' => $refunded,
            'forfeited' => $forfeited,
            'pending' => $pending,
            'failed_count' => $failedCount,
            'txn_count' => $txnCount,
            'avg_txn' => $txnCount > 0 ? intdiv($txnTotal, $txnCount) : 0,
        ];
    }

    /**
     * Confirmed revenue composition by payment type (for the type donut).
     *
     * @return Collection<int, array{label: string, total: int, cnt: int}>
     */
    public function groupByType(Builder $query): Collection
    {
        return $query
            ->where('payments.status', PaymentStatus::CONFIRMED->value)
            ->select('payment_type')
            ->selectRaw('SUM(amount) as total')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('payment_type')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->payment_type?->label() ?? '—',
                'total' => (int) $row->total,
                'cnt' => (int) $row->cnt,
            ])
            ->filter(fn ($r) => $r['total'] > 0)
            ->sortByDesc('total')
            ->values();
    }

    /**
     * All transactions grouped by status (for the status donut + legend).
     *
     * @return Collection<int, array{status: PaymentStatus, label: string, total: int, cnt: int}>
     */
    public function groupByStatus(Builder $query): Collection
    {
        return $query
            ->select('status')
            ->selectRaw('SUM(amount) as total')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'label' => $row->status?->label() ?? '—',
                'total' => (int) $row->total,
                'cnt' => (int) $row->cnt,
            ])
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Confirmed revenue per month (Y-m). Grouped in PHP (Carbon) rather than a
     * DB date function so it stays portable across MySQL (prod) and SQLite (tests)
     * and respects the app timezone.
     *
     * @return array{categories: array<int, string>, data: array<int, int>}
     */
    public function monthlyRevenue(Builder $query): array
    {
        $rows = $query
            ->where('payments.status', PaymentStatus::CONFIRMED->value)
            ->orderBy('payments.created_at')
            ->get(['payments.created_at', 'payments.amount']);

        $byMonth = [];
        foreach ($rows as $row) {
            $key = $row->created_at?->format('Y-m');
            if ($key === null) {
                continue;
            }
            $byMonth[$key] = ($byMonth[$key] ?? 0) + (int) $row->amount;
        }

        ksort($byMonth);

        return [
            'categories' => array_keys($byMonth),
            'data' => array_values($byMonth),
        ];
    }

    /**
     * Confirmed revenue grouped by one column of the related auction (category /
     * wilaya / entity), joined explicitly. The join itself is not entity-scoped,
     * but the incoming query is already restricted to the viewer's auctions
     * (citizen user_id, or whereHas('auction') + EntityScope for entity staff),
     * so the grouped rows never leak across tenants.
     *
     * @param  class-string  $nameModel  reference model providing the ->name label
     * @return Collection<int, array{name: string, total: int}>
     */
    private function groupByAuctionColumn(Builder $query, string $column, string $nameModel): Collection
    {
        $rows = $query
            ->where('payments.status', PaymentStatus::CONFIRMED->value)
            ->join('auctions', 'auctions.id', '=', 'payments.auction_id')
            ->select("auctions.{$column} as ref_id")
            ->selectRaw('SUM(payments.amount) as total')
            ->groupBy("auctions.{$column}")
            ->get();

        $ids = $rows->pluck('ref_id')->filter()->all();
        $names = $nameModel::whereIn('id', $ids)->get()->keyBy('id');

        return $rows
            ->map(fn ($row) => [
                'name' => $names[$row->ref_id]->name ?? '—',
                'total' => (int) $row->total,
            ])
            ->filter(fn ($r) => $r['total'] > 0)
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Sum the stored fee components (hammer / appraisal / TVA …) across every
     * award document in scope. The breakdown is read from the AWARD document's
     * meta.fees JSON (persisted at close) — no per-request recomputation.
     *
     * @return array<string, int>|null  null when there are no awarded auctions
     */
    public function feeBreakdown(?Builder $awards): ?array
    {
        if ($awards === null) {
            return null;
        }

        $acc = [
            'hammer_price' => 0,
            'appraisal_fee' => 0,
            'hammer_fee' => 0,
            'proportional_buyer' => 0,
            'work_session_fee' => 0,
            'tva' => 0,
            'buyer_total' => 0,
        ];
        $count = 0;

        foreach ($awards->get(['meta']) as $document) {
            $fees = $document->meta['fees'] ?? null;
            if (! is_array($fees)) {
                continue;
            }
            $count++;
            foreach (array_keys($acc) as $key) {
                $acc[$key] += (int) ($fees[$key] ?? 0);
            }
        }

        if ($count === 0) {
            return null;
        }

        $acc['_count'] = $count;

        return $acc;
    }
}
