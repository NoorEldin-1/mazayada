<?php

namespace App\Support;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Parsed, validated filter state for the Financial Reports module.
 *
 * Immutable: built once from the request, then applied to any number of freshly
 * scoped Eloquent queries (summary, distributions, transactions table, export).
 * All money is centimes; dates are Africa/Algiers (config app.timezone).
 *
 * `applyTo()` deliberately reaches the auction-level filters (category / wilaya /
 * entity / search) through whereHas('auction', ...) so the Auction EntityScope
 * still isolates entity accounts to their own rows — never a raw join that would
 * bypass it.
 */
final class ReportFilters
{
    public const PRESETS = ['today', '7d', '30d', 'this_month', 'quarter', 'this_year', 'all'];

    /**
     * @param  array<int, string>  $types     PaymentType values
     * @param  array<int, string>  $statuses  PaymentStatus values
     */
    public function __construct(
        public readonly ?CarbonImmutable $from,
        public readonly ?CarbonImmutable $to,
        public readonly string $preset,
        public readonly array $types,
        public readonly array $statuses,
        public readonly ?int $categoryId,
        public readonly ?int $wilayaId,
        public readonly ?string $entityId,
        public readonly ?int $minCentimes,
        public readonly ?int $maxCentimes,
        public readonly ?string $search,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $tz = config('app.timezone', 'Africa/Algiers');
        $now = CarbonImmutable::now($tz);

        [$from, $to, $preset] = self::resolveRange(
            (string) $request->query('preset', ''),
            $request->query('from'),
            $request->query('to'),
            $now,
            $tz,
        );

        return new self(
            from: $from,
            to: $to,
            preset: $preset,
            types: self::cleanEnum((array) $request->query('type', []), PaymentType::class),
            statuses: self::cleanEnum((array) $request->query('status', []), PaymentStatus::class),
            categoryId: $request->filled('category_id') ? (int) $request->query('category_id') : null,
            wilayaId: $request->filled('wilaya_id') ? (int) $request->query('wilaya_id') : null,
            entityId: $request->filled('entity_id') ? (string) $request->query('entity_id') : null,
            minCentimes: $request->filled('min') ? (int) round((float) $request->query('min') * 100) : null,
            maxCentimes: $request->filled('max') ? (int) round((float) $request->query('max') * 100) : null,
            search: $request->filled('search') ? trim((string) $request->query('search')) : null,
        );
    }

    /**
     * Apply the payment-level + auction-level filters to a Payment query.
     * Columns are table-qualified so the same call is safe whether or not the
     * caller later joins `auctions` (the distribution aggregates do).
     */
    public function applyTo(Builder $query, string $table = 'payments'): Builder
    {
        if ($this->from) {
            $query->where("{$table}.created_at", '>=', $this->from);
        }
        if ($this->to) {
            $query->where("{$table}.created_at", '<=', $this->to);
        }
        if ($this->types) {
            $query->whereIn("{$table}.payment_type", $this->types);
        }
        if ($this->statuses) {
            $query->whereIn("{$table}.status", $this->statuses);
        }
        if ($this->minCentimes !== null) {
            $query->where("{$table}.amount", '>=', $this->minCentimes);
        }
        if ($this->maxCentimes !== null) {
            $query->where("{$table}.amount", '<=', $this->maxCentimes);
        }

        $this->applyAuctionConstraints($query);

        return $query;
    }

    /**
     * Apply only the date-range + auction-level filters to a Document (award)
     * query — used by the fee-breakdown section. Payment type/status/amount don't
     * apply to award documents, so they're intentionally skipped.
     */
    public function applyToDocuments(Builder $query, string $table = 'documents'): Builder
    {
        if ($this->from) {
            $query->where("{$table}.created_at", '>=', $this->from);
        }
        if ($this->to) {
            $query->where("{$table}.created_at", '<=', $this->to);
        }

        $this->applyAuctionConstraints($query);

        return $query;
    }

    private function applyAuctionConstraints(Builder $query): void
    {
        $auctionFilters = array_filter([
            'category_id' => $this->categoryId,
            'wilaya_id' => $this->wilayaId,
            'entity_id' => $this->entityId,
        ], fn ($v) => $v !== null);

        if (! $auctionFilters && ! $this->search) {
            return;
        }

        $query->whereHas('auction', function (Builder $q) use ($auctionFilters) {
            foreach ($auctionFilters as $column => $value) {
                $q->where($column, $value);
            }
            if ($this->search) {
                $q->where('title_ar', 'LIKE', '%'.$this->search.'%');
            }
        });
    }

    /** Whether any filter narrows the default (all-time, unfiltered) view. */
    public function isActive(): bool
    {
        return $this->preset !== 'all'
            || $this->types || $this->statuses
            || $this->categoryId !== null || $this->wilayaId !== null || $this->entityId !== null
            || $this->minCentimes !== null || $this->maxCentimes !== null
            || $this->search !== null;
    }

    /**
     * The active filters as query params, for building links (export buttons,
     * preset chips) that preserve the current state.
     *
     * @return array<string, mixed>
     */
    public function toQuery(): array
    {
        return array_filter([
            'preset' => in_array($this->preset, ['all', 'custom'], true) ? null : $this->preset,
            'from' => $this->preset === 'custom' ? $this->from?->format('Y-m-d') : null,
            'to' => $this->preset === 'custom' ? $this->to?->format('Y-m-d') : null,
            'type' => $this->types ?: null,
            'status' => $this->statuses ?: null,
            'category_id' => $this->categoryId,
            'wilaya_id' => $this->wilayaId,
            'entity_id' => $this->entityId,
            'min' => $this->minCentimes !== null ? intdiv($this->minCentimes, 100) : null,
            'max' => $this->maxCentimes !== null ? intdiv($this->maxCentimes, 100) : null,
            'search' => $this->search,
        ], fn ($v) => $v !== null && $v !== []);
    }

    /** The same params minus the date range — the base for preset-switch links. */
    public function toQueryWithoutRange(): array
    {
        $params = $this->toQuery();
        unset($params['preset'], $params['from'], $params['to']);

        return $params;
    }

    /** Human label for the active range (tiles header + PDF). */
    public function rangeLabel(): string
    {
        if ($this->preset === 'custom') {
            return ($this->from?->format('Y-m-d') ?? '…').' — '.($this->to?->format('Y-m-d') ?? '…');
        }
        if ($this->preset === 'all') {
            return __('reports.range_all_time');
        }

        return __('reports.preset_'.$this->preset);
    }

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable, 2: string}
     */
    private static function resolveRange(string $preset, mixed $rawFrom, mixed $rawTo, CarbonImmutable $now, string $tz): array
    {
        // Explicit custom dates always win — so a persisted `preset` hidden field
        // in the filter form never overrides a range the user just typed.
        $from = self::parseDate($rawFrom, $tz)?->startOfDay();
        $to = self::parseDate($rawTo, $tz)?->endOfDay();
        if ($from || $to) {
            return [$from, $to, 'custom'];
        }

        return match ($preset) {
            'today' => [$now->startOfDay(), $now->endOfDay(), 'today'],
            '7d' => [$now->subDays(6)->startOfDay(), $now->endOfDay(), '7d'],
            '30d' => [$now->subDays(29)->startOfDay(), $now->endOfDay(), '30d'],
            'this_month' => [$now->startOfMonth(), $now->endOfMonth(), 'this_month'],
            'quarter' => [$now->startOfQuarter(), $now->endOfQuarter(), 'quarter'],
            'this_year' => [$now->startOfYear(), $now->endOfYear(), 'this_year'],
            default => [null, null, 'all'],
        };
    }

    private static function parseDate(mixed $value, string $tz): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', trim($value), $tz);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Keep only submitted values that are valid backing enum values.
     *
     * @param  array<int, mixed>  $values
     * @param  class-string<\BackedEnum>  $enum
     * @return array<int, string>
     */
    private static function cleanEnum(array $values, string $enum): array
    {
        $valid = array_map(fn ($case) => $case->value, $enum::cases());

        return array_values(array_filter(
            array_map(fn ($v) => (string) $v, $values),
            fn ($v) => in_array($v, $valid, true),
        ));
    }
}
