<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Auction;
use App\Support\Api\FormatsMoney;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compact auction representation for list/grid screens. Money is exposed as
 * { amount: <dinars>, formatted: <string> }.
 *
 * @mixin Auction
 */
class AuctionListResource extends JsonResource
{
    use FormatsMoney;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->localizedTitle(),
            'cover_photo_url' => $this->coverPhotoUrl(),
            'status' => $this->status?->value,
            'auction_type' => $this->auction_type?->value,
            'asset_class' => $this->asset_class?->value,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'wilaya' => $this->whenLoaded('wilaya', fn () => [
                'id' => $this->wilaya->id,
                'code' => $this->wilaya->code,
                'name' => $this->wilaya->name,
            ]),
            'opening_price' => $this->money($this->opening_price),
            'current_price' => $this->money($this->currentPrice()),
            'bid_count' => $this->bidCount(),
            'start_time' => $this->start_time?->toIso8601String(),
            'end_time' => $this->end_time?->toIso8601String(),
            'seconds_remaining' => $this->secondsRemaining(),
            'is_live' => $this->isLive(),
            'is_biddable' => $this->isBiddable(),
            'has_ended' => $this->hasEnded(),
        ];
    }

    /** Whole seconds until end_time, or 0 once the clock has run out. */
    protected function secondsRemaining(): int
    {
        if ($this->end_time === null || ! $this->end_time->isFuture()) {
            return 0;
        }

        return (int) now()->diffInSeconds($this->end_time);
    }
}
