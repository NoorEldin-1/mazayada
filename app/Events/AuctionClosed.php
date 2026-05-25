<?php

namespace App\Events;

use App\Models\Auction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionClosed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $auctionId;

    public ?string $winnerAlias;

    public ?int $finalPrice;

    public function __construct(Auction $auction, ?string $winnerAlias, ?int $finalPrice)
    {
        $this->auctionId = $auction->id;
        $this->winnerAlias = $winnerAlias;
        $this->finalPrice = $finalPrice;
    }

    public function broadcastOn(): array
    {
        return [new Channel('auction.'.$this->auctionId)];
    }

    public function broadcastAs(): string
    {
        return 'auction.closed';
    }

    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auctionId,
            'winner_alias' => $this->winnerAlias,
            'final_price' => $this->finalPrice,
        ];
    }
}
