<?php

namespace App\Events;

use App\Models\Auction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionExtended implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $auctionId;

    public int $newEndTime;

    public function __construct(Auction $auction)
    {
        $this->auctionId = $auction->id;
        $this->newEndTime = $auction->end_time->timestamp;
    }

    public function broadcastOn(): array
    {
        return [new Channel('auction.'.$this->auctionId)];
    }

    public function broadcastAs(): string
    {
        return 'auction.extended';
    }

    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auctionId,
            'new_end_time' => $this->newEndTime,
        ];
    }
}
