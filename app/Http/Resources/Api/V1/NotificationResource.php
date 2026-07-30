<?php

namespace App\Http\Resources\Api\V1;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An in-app notification row.
 *
 * `type` vs `channel` — these answer different questions and the client almost
 * always wants the first:
 *   type    = WHAT happened (outbid, auction_won, payment_confirmed, kyc_approved,
 *             commercial_register_approved, …). Branch on this; never parse the
 *             translated title. Null on rows created before the column existed.
 *   channel = HOW it was delivered (IN_APP | EMAIL | SMS | PUSH).
 *
 * @mixin UserNotification
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->event,
            'channel' => $this->channel?->value,
            'is_read' => (bool) $this->is_read,
            'action_url' => $this->action_url,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
