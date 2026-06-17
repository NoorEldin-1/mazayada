<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\PlaceBidRequest;
use App\Http\Resources\Api\V1\BidResource;
use App\Models\Auction;
use App\Services\BiddingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * @group Bidding
 *
 * Placing bids. Requires a verified (KYC-complete) account and a paid
 * registration on the auction. Rate-limited per user per auction.
 */
class BidController extends ApiController
{
    /**
     * Place a bid
     *
     * Submit a bid in WHOLE DINARS. Business-rule failures (too low, not
     * registered, not live, …) return 422 with the localized reason under
     * `errors.amount`.
     *
     * @bodyParam amount integer required The bid amount in dinars. Example: 15000
     */
    public function store(PlaceBidRequest $request, Auction $auction, BiddingService $bidding): JsonResponse
    {
        try {
            $bid = $bidding->placeBid(
                $auction,
                $request->user(),
                (int) $request->integer('amount') * 100,
                $request->ip(),
                $request->userAgent(),
            );
        } catch (RuntimeException $e) {
            // Service rules already return localized messages.
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }

        return $this->created([
            'bid' => new BidResource($bid),
            'current_price' => dinars($auction->currentPrice()),
        ], __('auctions.flash_bid_placed'));
    }
}
