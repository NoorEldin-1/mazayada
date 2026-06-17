<?php

namespace App\Events;

use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast every time a valid bid is placed.
 * Never expose the bidder's real identity — only the deterministic alias.
 *
 * ShouldBroadcastNow (not the queued ShouldBroadcast): the live-bid path must
 * reach every watcher immediately and must NOT depend on a running queue worker.
 * The broadcast HTTP call to Reverb happens inline within the bid request
 * (a few ms) — see BiddingService::placeBid.
 */
class BidPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $auctionId;

    public int $newPrice;

    public string $bidderAlias;

    public int $timestamp;

    public function __construct(Bid $bid, string $bidderAlias)
    {
        $this->auctionId = $bid->auction_id;
        $this->newPrice = (int) $bid->amount;
        $this->bidderAlias = $bidderAlias;
        $this->timestamp = $bid->bid_time?->timestamp ?? now()->timestamp;
    }

    public function broadcastOn(): array
    {
        return [new Channel('auction.'.$this->auctionId)];
    }

    public function broadcastAs(): string
    {
        return 'bid.placed';
    }

    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auctionId,
            // new_price is in centimes (the storage unit, kept for the web client);
            // new_price_dinars is the same amount in dinars for the mobile client,
            // matching the REST API's money unit.
            'new_price' => $this->newPrice,
            'new_price_dinars' => dinars($this->newPrice),
            'bidder_alias' => $this->bidderAlias,
            'timestamp' => $this->timestamp,
        ];
    }
}
