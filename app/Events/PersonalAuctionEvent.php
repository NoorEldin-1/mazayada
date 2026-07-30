<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Something that concerns ONE participant on ONE auction: they were outbid, they
 * won, they lost, their payment cleared.
 *
 * Broadcast on the private `auction.{auctionId}.user.{userId}` channel, which
 * routes/channels.php has always authorised but which nothing published to — so
 * the client had to poll to notice it had been outbid. This is the realtime
 * counterpart of the in-app notification row (same `type` vocabulary as
 * NotificationResource.type), not a replacement: the row is the durable record,
 * this is the instant nudge.
 *
 * ShouldBroadcastNow — like BidPlaced, these must land immediately and must not
 * depend on a queue worker running. Deliberately carries NO personal data: the
 * client re-fetches the auction (or reads /notifications) for detail.
 */
class PersonalAuctionEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  string  $type  outbid | auction_won | auction_lost | payment_confirmed | payment_failed
     * @param  array<string, mixed>  $payload  extra scalars (e.g. amount in dinars)
     */
    public function __construct(
        public string $userId,
        public string $auctionId,
        public string $type,
        public array $payload = [],
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("auction.{$this->auctionId}.user.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'auction.personal';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge([
            'type' => $this->type,
            'auction_id' => $this->auctionId,
            'timestamp' => now()->timestamp,
        ], $this->payload);
    }
}
