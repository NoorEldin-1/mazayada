<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A citizen's subscription. days_remaining is computed on the server clock.
 *
 * @mixin Subscription
 */
class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'plan' => $this->plan ? (new SubscriptionPlanResource($this->plan))->resolve($request) : null,
            'started_at' => $this->started_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'auto_renew' => (bool) $this->auto_renew,
            'days_remaining' => $this->daysRemaining(),
        ];
    }
}
