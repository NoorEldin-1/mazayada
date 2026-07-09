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
        if ($request->filled('asset_class')) {
            $query->where('asset_class', $request->input('asset_class'));
        }
        if ($request->filled('commune')) {
            $query->where('commune_id', $request->input('commune'));
        }

        $perPage = min(max((int) $request->input('per_page', 12), 1), 50);

        $sort = $request->input('sort');
        if ($sort === 'opening_price') {
            $query->orderBy('opening_price', 'desc');
        } elseif ($sort === 'bid_count') {
            $query->withCount(['bids as valid_bids_count' => fn ($q) => $q->where('is_valid', true)])->orderBy('valid_bids_count', 'desc');
        } else {
            $query->latest('start_time');
        }

        $auctions = $query->paginate($perPage)->withQueryString();

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
        $appeal = $auction->appealBy($user);

        return [
            'can_bid' => $user->canBid(),
            'is_participant' => $participant?->isFullyRegistered() ?? false,
            // §2.3 — a Commercial Register-gated auction blocks paying ANY fee until
            // the user holds a valid register. Surfaced so the client can guide the
            // user to the CR screen BEFORE a checkout attempt returns 422.
            'has_commerce_register' => $user->hasCommerceRegister(),
            'commerce_register_blocked' => $auction->requires_commerce_register && ! $user->hasCommerceRegister(),
            // Book access (free or purchased) is the prerequisite for registering.
            'has_book_access' => $auction->hasBookAccess($user),
            'book_purchased' => (bool) ($participant?->book_purchased ?? false),
            'deposit_paid' => (bool) ($participant?->deposit_paid ?? false),
            'is_winner' => $auction->winner_user_id === $user->id,
            'can_appeal' => $auction->canBeAppealedBy($user),
            'existing_appeal' => $appeal ? [
                'id' => $appeal->id,
                'status' => $appeal->status?->publicStatus()->value,
                'status_label' => $appeal->status?->publicLabel(),
            ] : null,
            'has_final_payment' => app(\App\Services\PaymentService::class)->confirmedFinalPayment($auction, $user),
        ];
    }
}
