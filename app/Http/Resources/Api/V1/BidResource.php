<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Bid;
use App\Support\Api\FormatsMoney;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A single bid for public display. Exposes ONLY the deterministic bidder alias —
 * never the real bidder identity (spec §6.5). Amounts are in dinars.
 *
 * @mixin Bid
 */
class BidResource extends JsonResource
{
    use FormatsMoney;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'amount' => $this->money($this->amount),
            'bidder_alias' => $this->bidderAlias(),
            'bid_time' => $this->bid_time?->toIso8601String(),
        ];
    }
}
