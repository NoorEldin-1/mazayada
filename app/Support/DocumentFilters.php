<?php

namespace App\Support;

use App\Enums\DocumentType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Parsed, validated filter state for the citizen Document Library (الوثائق).
 *
 * Immutable: built once from the request, then applied to the (already
 * user-scoped) Document query built by DocumentLibraryService. Dates filter on
 * the document's issue date (documents.created_at), Africa/Algiers.
 *
 * Auction-level filters (category / wilaya / entity / search) reach the auction
 * through whereHas('auction', ...) so the Auction EntityScope still applies —
 * never a raw join that would bypass it. The full-text search also matches the
 * document's own title, so it works even for documents whose auction was removed.
 */
final class DocumentFilters
{
    public const PRESETS = ['today', '7d', '30d', 'this_month', 'this_year', 'all'];

    public const SORTS = ['recent', 'oldest', 'auction'];

    /**
     * @param  array<int, string>  $types  DocumentType values (AUCTION_REPORT excluded upstream)
     */
    public function __construct(
        public readonly ?CarbonImmutable $from,
        public readonly ?CarbonImmutable $to,
        public readonly string $preset,
        public readonly array $types,
        public readonly ?int $categoryId,
        public readonly ?int $wilayaId,
        public readonly ?string $entityId,
        public readonly ?string $search,
        public readonly string $sort,
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

        $sort = (string) $request->query('sort', 'recent');

        return new self(
            from: $from,
            to: $to,
            preset: $preset,
            types: self::cleanTypes((array) $request->query('type', [])),
            categoryId: $request->filled('category_id') ? (int) $request->query('category_id') : null,
            wilayaId: $request->filled('wilaya_id') ? (int) $request->query('wilaya_id') : null,
            entityId: $request->filled('entity_id') ? (string) $request->query('entity_id') : null,
            search: $request->filled('search') ? trim((string) $request->query('search')) : null,
            sort: in_array($sort, self::SORTS, true) ? $sort : 'recent',
        );
    }

    /**
     * Apply the date-range, type, auction-level and search filters to an already
     * user-scoped Document query, then order it.
     */
    public function applyTo(Builder $query, string $table = 'documents'): Builder
    {
        if ($this->from) {
            $query->where("{$table}.created_at", '>=', $this->from);
        }
        if ($this->to) {
            $query->where("{$table}.created_at", '<=', $this->to);
        }
        if ($this->types) {
            $query->whereIn("{$table}.type", $this->types);
        }

        $this->applySearchAndAuction($query, $table);
        $this->applySort($query, $table);

        return $query;
    }

    /**
     * Search matches EITHER the document's own title OR its auction's localized
     * title; the category / wilaya / entity filters constrain the auction. When a
     * search term is present it must not hide title-only matches, so the auction
     * constraint is folded into the same OR group as the document-title match.
     */
    private function applySearchAndAuction(Builder $query, string $table): void
    {
        $auctionFilters = array_filter([
            'category_id' => $this->categoryId,
            'wilaya_id' => $this->wilayaId,
            'entity_id' => $this->entityId,
        ], fn ($v) => $v !== null);

        if ($auctionFilters) {
            $query->whereHas('auction', function (Builder $q) use ($auctionFilters) {
                foreach ($auctionFilters as $column => $value) {
                    $q->where($column, $value);
                }
            });
        }

        if ($this->search === null || $this->search === '') {
            return;
        }

        $term = '%'.$this->search.'%';
        $query->where(function (Builder $q) use ($term, $table) {
            $q->where("{$table}.title", 'LIKE', $term)
                ->orWhereHas('auction', function (Builder $a) use ($term) {
                    $a->where('title_ar', 'LIKE', $term)
                        ->orWhere('title_fr', 'LIKE', $term)
                        ->orWhere('title_en', 'LIKE', $term);
                });
        });
    }

    private function applySort(Builder $query, string $table): void
    {
        match ($this->sort) {
            'oldest' => $query->orderBy("{$table}.created_at"),
            // Group a user's paperwork by auction, newest doc first within each.
            'auction' => $query->orderBy("{$table}.auction_id")->orderByDesc("{$table}.created_at"),
            default => $query->orderByDesc("{$table}.created_at"),
        };
    }

    /** Whether any filter narrows the default (all-time, all-types) view. */
    public function isActive(): bool
    {
        return $this->preset !== 'all'
            || $this->types
            || $this->categoryId !== null || $this->wilayaId !== null || $this->entityId !== null
            || $this->search !== null
            || $this->sort !== 'recent';
    }

    /**
     * The active filters as query params, for building links (view toggle, preset
     * chips) that preserve the current state.
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
            'category_id' => $this->categoryId,
            'wilaya_id' => $this->wilayaId,
            'entity_id' => $this->entityId,
            'search' => $this->search,
            'sort' => $this->sort !== 'recent' ? $this->sort : null,
        ], fn ($v) => $v !== null && $v !== []);
    }

    /** The same params minus the date range — the base for preset-switch links. */
    public function toQueryWithoutRange(): array
    {
        $params = $this->toQuery();
        unset($params['preset'], $params['from'], $params['to']);

        return $params;
    }

    /** Human label for the active range (header chip). */
    public function rangeLabel(): string
    {
        if ($this->preset === 'custom') {
            return ($this->from?->format('Y-m-d') ?? '…').' — '.($this->to?->format('Y-m-d') ?? '…');
        }
        if ($this->preset === 'all') {
            return __('documents.lib_range_all');
        }

        return __('documents.lib_preset_'.$this->preset);
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
     * Keep only submitted values that are valid DocumentType values, minus the
     * admin-only AUCTION_REPORT which the library never exposes.
     *
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    private static function cleanTypes(array $values): array
    {
        $valid = array_diff(
            array_map(fn ($case) => $case->value, DocumentType::cases()),
            [DocumentType::AUCTION_REPORT->value],
        );

        return array_values(array_filter(
            array_map(fn ($v) => (string) $v, $values),
            fn ($v) => in_array($v, $valid, true),
        ));
    }
}
