<?php

namespace App\Support;

use App\Enums\AuctionStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies the public auction browse filters to an Eloquent query — the single
 * source of truth for the filter vocabulary shared by the web browse page and the
 * mobile API. Both speak the same query-string tokens so a filtered URL and a
 * mobile request behave identically.
 *
 * The query is expected to already be scoped to public auctions (Auction::public()).
 * All monetary inputs are DINARS on the wire and converted to centimes here.
 */
final class AuctionFilters
{
    /** User-facing status tokens → the enum values they expand to. */
    private const STATUS_TOKENS = [
        'upcoming' => [AuctionStatus::PUBLISHED],
        'live' => [AuctionStatus::ACTIVE, AuctionStatus::EXTENDED],
        'closed' => [AuctionStatus::CLOSED],
    ];

    /**
     * Apply every filter present in $params to $query.
     *
     * @param  array<string, mixed>  $params
     */
    public static function apply(Builder $query, array $params): Builder
    {
        // Keyword — title in any locale, or the free-text asset location.
        if (self::filled($params, 'q')) {
            $term = (string) $params['q'];
            $query->where(function ($w) use ($term): void {
                $w->where('title_ar', 'LIKE', '%'.$term.'%')
                    ->orWhere('title_fr', 'LIKE', '%'.$term.'%')
                    ->orWhere('title_en', 'LIKE', '%'.$term.'%')
                    ->orWhere('asset_location', 'LIKE', '%'.$term.'%');
            });
        }

        if (self::filled($params, 'category')) {
            $query->where('category_id', $params['category']);
        }
        if (self::filled($params, 'wilaya')) {
            $query->where('wilaya_id', $params['wilaya']);
        }
        if (self::filled($params, 'commune')) {
            $query->where('commune_id', $params['commune']);
        }
        if (self::filled($params, 'type')) {
            $query->where('auction_type', $params['type']);
        }

        // Status — accepts user-facing tokens (upcoming/live/closed) AND raw enum
        // values (PUBLISHED/ACTIVE/…) for backward compatibility. An unknown token
        // set yields nothing rather than silently dropping the filter.
        if (self::filled($params, 'status')) {
            $values = self::resolveStatuses((array) $params['status']);
            $query->whereIn('status', $values ?: ['__none__']);
        }

        // Multi-value asset filters.
        if (self::filled($params, 'asset_class')) {
            $query->whereIn('asset_class', (array) $params['asset_class']);
        }
        if (self::filled($params, 'condition')) {
            $query->whereIn('condition', (array) $params['condition']);
        }

        // Commercial-register requirement — explicit yes/no; blank = any. Checked
        // with a presence + non-empty-string test rather than filled() because "0"
        // (not required) is meaningful and filled() would treat it as empty.
        if (array_key_exists('requires_cr', $params) && $params['requires_cr'] !== '' && $params['requires_cr'] !== null) {
            $query->where('requires_commerce_register', filter_var($params['requires_cr'], FILTER_VALIDATE_BOOLEAN));
        }

        // Opening-price range — the wire speaks dinars; storage is centimes.
        if (self::filled($params, 'price_min')) {
            $query->where('opening_price', '>=', (int) $params['price_min'] * 100);
        }
        if (self::filled($params, 'price_max')) {
            $query->where('opening_price', '<=', (int) $params['price_max'] * 100);
        }

        return $query;
    }

    /**
     * Apply the sort token. Newest first by default. Legacy aliases
     * (opening_price, bid_count) are kept so older clients don't break.
     */
    public static function sort(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('opening_price'),
            'price_desc', 'opening_price' => $query->orderByDesc('opening_price'),
            'most_bids', 'bid_count' => $query
                ->withCount(['bids as valid_bids_count' => fn ($b) => $b->where('is_valid', true)])
                ->orderByDesc('valid_bids_count'),
            'ending_soon' => $query->orderByRaw('end_time IS NULL, end_time ASC'),
            default => $query->latest('start_time'),
        };
    }

    /**
     * Expand a mix of status tokens / raw enum values to concrete enum values.
     *
     * @param  array<int, mixed>  $tokens
     * @return array<int, string>
     */
    private static function resolveStatuses(array $tokens): array
    {
        return collect($tokens)
            ->flatMap(function ($token): array {
                if (isset(self::STATUS_TOKENS[$token])) {
                    return array_map(fn (AuctionStatus $s) => $s->value, self::STATUS_TOKENS[$token]);
                }

                // Raw enum value passthrough (e.g. an older client sending ACTIVE).
                return AuctionStatus::tryFrom((string) $token) ? [(string) $token] : [];
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private static function filled(array $params, string $key): bool
    {
        return isset($params[$key]) && $params[$key] !== '' && $params[$key] !== [];
    }
}
