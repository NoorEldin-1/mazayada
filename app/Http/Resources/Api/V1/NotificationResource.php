<?php

namespace App\Http\Resources\Api\V1;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An in-app notification row.
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
            'channel' => $this->channel?->value,
            'is_read' => (bool) $this->is_read,
            'action_url' => $this->action_url,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
