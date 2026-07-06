<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Appeal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A citizen appeal against an auction result. The client sees only the 3 public
 * states (the internal admin↔entity handoffs collapse to PENDING), and the
 * admin's final note only once the appeal is terminal.
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
            'status' => $this->status?->publicStatus()->value,
            'status_label' => $this->status?->publicLabel(),
            'admin_response' => $this->status?->isTerminal() ? $this->admin_response : null,
            'entity_response' => $this->status?->isTerminal() ? $this->entity_response : null,
            'auction' => $this->whenLoaded('auction', fn () => $this->auction ? [
                'id' => $this->auction->id,
                'title' => $this->auction->localizedTitle(),
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'forwarded_at' => $this->forwarded_at?->toIso8601String(),
            'entity_decided_at' => $this->entity_decided_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}
