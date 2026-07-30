<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Auction;
use Illuminate\Http\Request;

/**
 * An auction as seen from INSIDE the user's participation — the compact list
 * shape plus the viewer's own state on it, which is the whole point of the
 * "my auctions" screen ("you're the highest bidder", "you were outbid", "pay the
 * balance").
 *
 * The per-viewer figures are resolved in bulk by the controller and injected
 * here, so rendering a page of rows stays a fixed number of queries rather than
 * one per auction.
 *
 * @mixin Auction
 */
class MyAuctionResource extends AuctionListResource
{
    /**
     * @param  array{
     *     deposit_paid?: bool,
     *     book_purchased?: bool,
     *     registered_at?: ?string,
     *     my_highest_bid?: ?int,
     *     is_winning?: bool,
     *     final_payment_status?: ?string
     * }  $viewer  pre-resolved per-user state (centimes for money)
     */
    public function __construct($resource, private readonly array $viewer = [])
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $myHighestBid = $this->viewer['my_highest_bid'] ?? null;

        return array_merge(parent::toArray($request), [
            // Bidding position.
            'my_highest_bid' => $myHighestBid !== null ? $this->money($myHighestBid) : null,
            // Live auctions: the user currently holds the top bid.
            // Closed auctions: the user is the declared winner.
            'is_winning' => (bool) ($this->viewer['is_winning'] ?? false),
            'is_winner' => $this->hasEnded() && (bool) ($this->viewer['is_winning'] ?? false),

            // Registration state (mirrors meta.viewer on the auction detail).
            'deposit_paid' => (bool) ($this->viewer['deposit_paid'] ?? false),
            'book_purchased' => (bool) ($this->viewer['book_purchased'] ?? false),
            'registered_at' => $this->viewer['registered_at'] ?? null,

            // Winner's balance: null = no final payment started yet, otherwise the
            // PaymentStatus of the latest one (PENDING | CONFIRMED | FAILED | …).
            'final_payment_status' => $this->viewer['final_payment_status'] ?? null,
        ]);
    }
}
