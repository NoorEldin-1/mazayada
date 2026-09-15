<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SubscriptionPlan;
use App\Support\Api\FormatsMoney;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A Premium plan. `features` are server-translated so a new feature needs no
 * app release.
 *
 * @mixin SubscriptionPlan
 */
class SubscriptionPlanResource extends JsonResource
{
    use FormatsMoney;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'period' => $this->period?->value,
            'period_label' => $this->period?->label(),
            'price' => $this->money($this->price),
            'features' => $this->localizedFeatures(),
            'is_recommended' => (bool) $this->is_recommended,
        ];
    }
}
