<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AuctionStatus;
use App\Enums\InspectionQuestionStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\AuctionListResource;
use App\Http\Resources\Api\V1\AuctionResource;
use App\Http\Resources\Api\V1\BidResource;
use App\Http\Resources\Api\V1\QuestionResource;
use App\Models\Auction;
use App\Services\AuctionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Auctions
 *
 * Public, read-only auction browsing. Money is returned in dinars; bidders are
 * shown only by their deterministic alias.
 */
class AuctionController extends ApiController
{
    /** Statuses that are visible to the public (mirrors Auction::scopePublic). */
    private const PUBLIC_STATUSES = [
        AuctionStatus::PUBLISHED,
        AuctionStatus::ACTIVE,
        AuctionStatus::EXTENDED,
        AuctionStatus::CLOSED,
    ];

    /**
     * List auctions
     *
     * Paginated, filterable list of public auctions.
     *
     * @unauthenticated
     *
     * @queryParam q string Search in the Arabic title. Example: سيارة
     * @queryParam category string Filter by category id.
     * @queryParam wilaya integer Filter by wilaya id. Example: 16
     * @queryParam status string One of PUBLISHED, ACTIVE, EXTENDED, CLOSED. Example: ACTIVE
     * @queryParam type string Auction type: SALE or LEASE. Example: SALE
     * @queryParam per_page integer Items per page (1-50, default 12). Example: 12
     */
    public function index(Request $request): JsonResponse
    {
        $query = Auction::public()->with(['category', 'wilaya']);

        if ($request->filled('q')) {
            $query->where('title_ar', 'LIKE', '%'.$request->input('q').'%');
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }
        if ($request->filled('wilaya')) {
            $query->where('wilaya_id', $request->input('wilaya'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('type')) {
            $query->where('auction_type', $request->input('type'));
        }

        $perPage = min(max((int) $request->input('per_page', 12), 1), 50);

        $auctions = $query->latest('start_time')->paginate($perPage)->withQueryString();

        return $this->paginated($auctions, AuctionListResource::class);
    }

    /**
     * Get an auction
     *
     * Full detail for a single public auction. If the bidding clock has run out
     * but the auction is not yet finalised, it is closed on read (winner + final
     * price) so the response is canonical.
     *
     * @unauthenticated
     */
    public function show(Request $request, Auction $auction, AuctionService $auctions): JsonResponse
    {
        $this->ensurePublic($auction);

        // Lazy close-on-view — parity with the web show page.
        if ($auction->hasEnded()) {
            $auctions->close($auction);
            $auction->refresh();
        }

        $auction->load(['entity', 'category', 'wilaya', 'commune']);

        return $this->ok(new AuctionResource($auction), null, [
            'viewer' => $this->viewerContext($auction, $request->user()),
        ]);
    }

    /**
     * Latest bids
     *
     * The most recent valid bids (alias only) plus the current price. Use this to
     * hydrate the bid history, or poll it as a fallback when the realtime socket
     * is unavailable.
     *
     * @unauthenticated
     *
     * @queryParam limit integer Number of bids to return (1-50, default 10). Example: 10
     */
    public function latestBids(Request $request, Auction $auction): JsonResponse
    {
        $this->ensurePublic($auction);

        $limit = min(max((int) $request->input('limit', 10), 1), 50);

        $bids = $auction->bids()
            ->where('is_valid', true)
            ->latest('bid_time')
            ->limit($limit)
            ->get();

        return $this->ok(BidResource::collection($bids)->resolve($request), null, [
            'current_price' => dinars($auction->currentPrice()),
            'bid_count' => $auction->bidCount(),
            'status' => $auction->status?->value,
        ]);
    }

    /**
     * Current price
     *
     * A cheap, pollable snapshot of the live price and clock for an auction.
     *
     * @unauthenticated
     */
    public function price(Auction $auction): JsonResponse
    {
        $this->ensurePublic($auction);

        return $this->ok([
            'current_price' => dinars($auction->currentPrice()),
            'current_price_formatted' => dzd($auction->currentPrice()),
            'bid_count' => $auction->bidCount(),
            'status' => $auction->status?->value,
            'end_time' => $auction->end_time?->toIso8601String(),
            'is_biddable' => $auction->isBiddable(),
            'has_ended' => $auction->hasEnded(),
        ]);
    }

    /**
     * Public Q&A
     *
     * Answered, public inspection questions for an auction (asker not identified).
     *
     * @unauthenticated
     */
    public function questions(Request $request, Auction $auction): JsonResponse
    {
        $this->ensurePublic($auction);

        $questions = $auction->inspectionQuestions()
            ->where('is_public', true)
            ->where('status', InspectionQuestionStatus::ANSWERED)
            ->latest()
            ->paginate(20);

        return $this->paginated($questions, QuestionResource::class);
    }

    /** Abort with 404 for non-public auctions (don't leak drafts/cancelled). */
    private function ensurePublic(Auction $auction): void
    {
        abort_unless(in_array($auction->status, self::PUBLIC_STATUSES, true), 404);
    }

    /**
     * Viewer-specific context for an authenticated request (null for guests):
     * their registration/participation state on this auction.
     *
     * @return array<string, mixed>|null
     */
    private function viewerContext(Auction $auction, $user): ?array
    {
        if (! $user) {
            return null;
        }

        $participant = $auction->participants()->where('user_id', $user->id)->first();

        return [
            'can_bid' => $user->canBid(),
            'is_participant' => $participant?->isFullyRegistered() ?? false,
            'condition_book_acknowledged' => $participant?->condition_book_acknowledged_at !== null,
            'deposit_paid' => (bool) ($participant?->deposit_paid ?? false),
            'entry_fee_paid' => (bool) ($participant?->entry_fee_paid ?? false),
            'book_purchased' => (bool) ($participant?->book_purchased ?? false),
            'is_winner' => $auction->winner_user_id === $user->id,
        ];
    }
}
