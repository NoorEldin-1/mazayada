<?php

namespace App\Support\Api;

use Illuminate\Support\Collection;

/**
 * Converts a FinancialReportService report into the API money contract.
 *
 * The service works in integer centimes (the storage unit) because that is what
 * the ledger sums. Every other API surface — including /reports/transactions,
 * which goes through FormatsMoney — speaks DINARS, so returning the service
 * array verbatim put a silent ×100 discrepancy between two panels of the same
 * screen. Everything monetary is normalised here to the standard
 * `{ amount, formatted }` shape.
 *
 * Non-money fields (counts, labels, month keys) pass through untouched. The
 * chart series keeps a flat integer array — in dinars — because a chart wants
 * plottable numbers, not objects; `series.unit` states that explicitly.
 */
final class ReportMoneyFormatter
{
    /** Keys of `summary` that are counts, not money. */
    private const SUMMARY_COUNTS = ['failed_count', 'txn_count'];

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    public static function format(array $report): array
    {
        return [
            'summary' => self::summary($report['summary'] ?? []),
            'by_type' => self::rows($report['by_type'] ?? [], 'total'),
            'by_status' => self::rows($report['by_status'] ?? [], 'total'),
            'by_category' => self::rows($report['by_category'] ?? [], 'total'),
            'by_wilaya' => self::rows($report['by_wilaya'] ?? [], 'total'),
            'by_entity' => self::rows($report['by_entity'] ?? [], 'total'),
            'series' => self::series($report['series'] ?? []),
            'fees' => self::fees($report['fees'] ?? null),
        ];
    }

    /**
     * @param  array<string, int>  $summary
     * @return array<string, mixed>
     */
    private static function summary(array $summary): array
    {
        $out = [];

        foreach ($summary as $key => $value) {
            $out[$key] = in_array($key, self::SUMMARY_COUNTS, true)
                ? (int) $value
                : self::money((int) $value);
        }

        return $out;
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private static function rows(iterable $rows, string $moneyKey): array
    {
        $rows = $rows instanceof Collection ? $rows->all() : (array) $rows;

        return array_values(array_map(function (array $row) use ($moneyKey) {
            if (array_key_exists($moneyKey, $row)) {
                $row[$moneyKey] = self::money((int) $row[$moneyKey]);
            }

            // `status` is a PaymentStatus enum on the by_status rows.
            if (isset($row['status']) && $row['status'] instanceof \BackedEnum) {
                $row['status'] = $row['status']->value;
            }

            return $row;
        }, $rows));
    }

    /**
     * @param  array{categories?: array<int, string>, data?: array<int, int>}  $series
     * @return array<string, mixed>
     */
    private static function series(array $series): array
    {
        return [
            'categories' => array_values($series['categories'] ?? []),
            'data' => array_map(fn ($v) => dinars((int) $v), array_values($series['data'] ?? [])),
            'unit' => 'DZD',
        ];
    }

    /**
     * @param  array<string, int>|null  $fees
     * @return array<string, mixed>|null
     */
    private static function fees(?array $fees): ?array
    {
        if ($fees === null) {
            return null;
        }

        $out = [];

        foreach ($fees as $key => $value) {
            // `_count` is how many award documents fed the breakdown.
            $out[$key] = $key === '_count' ? (int) $value : self::money((int) $value);
        }

        return $out;
    }

    /**
     * @return array{amount: int, formatted: string}
     */
    private static function money(int $centimes): array
    {
        return ['amount' => dinars($centimes), 'formatted' => dzd($centimes)];
    }
}
