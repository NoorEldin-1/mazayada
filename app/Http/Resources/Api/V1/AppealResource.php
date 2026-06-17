<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Appeal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A citizen appeal/complaint with its (optional) admin response.
 *
 * @mixin Appeal
 */
class AppealResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'reason' => $this->reason,
            'status' => $this->status?->value,
            'admin_response' => $this->admin_response,
            'auction' => $this->whenLoaded('auction', fn () => $this->auction ? [
                'id' => $this->auction->id,
                'title' => $this->auction->localizedTitle(),
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}
