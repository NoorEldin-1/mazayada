<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Auction;
use App\Models\Document;
use App\Models\User;
use App\Support\DocumentFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Assembles the citizen's personal document library (الوثائق) — every generated
 * PDF tied to an auction the user has engaged with: the award / payment receipt /
 * delivery report they own, plus the condition book of any auction they took part
 * in and can access.
 *
 * Every row returned here already passes DocumentPolicy@download; the scoping
 * below deliberately mirrors that policy so the list never shows a document the
 * user could not actually open.
 */
class DocumentLibraryService
{
    /** The user's filtered, ordered, eager-loaded document query. */
    public function query(User $user, DocumentFilters $filters): Builder
    {
        $query = $this->scoped($user)
            ->with(['auction.category', 'auction.wilaya', 'auction.entity']);

        return $filters->applyTo($query);
    }

    /**
     * Per-type counts + total size for the stat tiles. Runs one grouped aggregate
     * over the same scoped set (no filters — the tiles describe the whole library).
     *
     * @return array{total:int, books:int, awards:int, receipts:int, total_bytes:int}
     */
    public function stats(User $user): array
    {
        // No eager loads here: the grouped aggregate selects only `type`, so a
        // `with('auction')` would try to read the unselected `auction_id` and trip
        // the model's strict missing-attribute guard.
        $rows = $this->scoped($user)
            ->select('documents.type', DB::raw('COUNT(*) as c'), DB::raw('COALESCE(SUM(documents.file_size), 0) as bytes'))
            ->groupBy('documents.type')
            ->get();

        $count = fn (DocumentType $type) => (int) ($rows->firstWhere('type', $type)?->c ?? 0);

        return [
            'total' => (int) $rows->sum('c'),
            'books' => $count(DocumentType::CONDITION_BOOK),
            'awards' => $count(DocumentType::AWARD),
            // Receipts + delivery reports share one tile ("financial / handover").
            'receipts' => $count(DocumentType::PAYMENT_RECEIPT) + $count(DocumentType::DELIVERY_REPORT),
            'total_bytes' => (int) $rows->sum('bytes'),
        ];
    }

    /**
     * Group a loaded page of documents by their auction, preserving the incoming
     * order (the grouped view sorts by auction upstream). A document whose auction
     * was removed falls into a null-keyed bucket rendered as "unlinked".
     *
     * @param  Collection<int, Document>  $documents
     * @return Collection<int, array{auction: ?Auction, documents: Collection<int, Document>}>
     */
    public function groupByAuction(Collection $documents): Collection
    {
        return $documents
            ->groupBy(fn (Document $d) => $d->auction_id ?? '__none__')
            ->map(fn (Collection $docs) => [
                'auction' => $docs->first()->auction,
                'documents' => $docs->values(),
            ])
            ->values();
    }

    /**
     * The distinct categories / wilayas / entities present across the user's own
     * auctions — used to populate the filter dropdowns with only relevant options.
     *
     * @return array{categories: Collection, wilayas: Collection, entities: Collection}
     */
    public function filterOptions(User $user): array
    {
        $ids = $this->userAuctionIds($user);

        if ($ids->isEmpty()) {
            return ['categories' => collect(), 'wilayas' => collect(), 'entities' => collect()];
        }

        $auctions = Auction::query()
            ->whereKey($ids)
            ->with(['category', 'wilaya', 'entity'])
            ->get();

        return [
            'categories' => $auctions->pluck('category')->filter()->unique('id')->sortBy('name')->values(),
            'wilayas' => $auctions->pluck('wilaya')->filter()->unique('id')->sortBy('name')->values(),
            'entities' => $auctions->pluck('entity')->filter()->unique('id')->sortBy('name')->values(),
        ];
    }

    /**
     * The user-scoped Document builder BEFORE filters and WITHOUT eager loads:
     * award/receipt/delivery rows the user owns, plus condition books of auctions
     * they engaged with and can access. AUCTION_REPORT (admin-only) is always
     * excluded. Callers add their own `with()` / `select()` as needed.
     */
    private function scoped(User $user): Builder
    {
        $bookAuctionIds = $this->bookAuctionIds($user);

        return Document::query()
            ->where('documents.type', '!=', DocumentType::AUCTION_REPORT)
            ->where(function (Builder $q) use ($user, $bookAuctionIds) {
                $q->where('documents.user_id', $user->id);

                if ($bookAuctionIds->isNotEmpty()) {
                    $q->orWhere(fn (Builder $b) => $b
                        ->where('documents.type', DocumentType::CONDITION_BOOK)
                        ->whereIn('documents.auction_id', $bookAuctionIds->all()));
                }
            });
    }

    /**
     * Auctions the user has engaged with: participated in, won, or bought the
     * condition book for. The universe the library is scoped to.
     *
     * @return Collection<int, string>
     */
    public function userAuctionIds(User $user): Collection
    {
        $participated = $user->participations()->pluck('auction_id');
        $won = $user->wonAuctions()->pluck('id');
        $purchased = $user->payments()
            ->where('payment_type', PaymentType::BOOK_PURCHASE)
            ->where('status', PaymentStatus::CONFIRMED)
            ->pluck('auction_id');

        return $participated->merge($won)->merge($purchased)->filter()->unique()->values();
    }

    /**
     * Of the user's engaged auctions, the ones whose condition book they may read:
     * the book is free (book_price = 0) or they hold a confirmed BOOK_PURCHASE —
     * i.e. Auction::hasBookAccess, resolved in bulk.
     *
     * @return Collection<int, string>
     */
    public function bookAuctionIds(User $user): Collection
    {
        $ids = $this->userAuctionIds($user);

        if ($ids->isEmpty()) {
            return collect();
        }

        return Auction::query()
            ->whereKey($ids)
            ->where(function (Builder $q) use ($user) {
                $q->where('book_price', 0)
                    ->orWhereHas('payments', fn (Builder $p) => $p
                        ->where('user_id', $user->id)
                        ->where('payment_type', PaymentType::BOOK_PURCHASE)
                        ->where('status', PaymentStatus::CONFIRMED));
            })
            ->pluck('id');
    }
}
