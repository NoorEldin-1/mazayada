<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\EmailRecoveryStatus;
use App\Models\EmailRecoveryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A "lost my email" recovery request as the (unauthenticated) client sees it.
 * The requested address is masked because the status lookup only needs a NIN.
 *
 * @mixin EmailRecoveryRequest
 */
class EmailRecoveryResource extends JsonResource
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
            'new_email_masked' => mask_email($this->new_email),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'rejection_reason' => $this->status === EmailRecoveryStatus::REJECTED ? $this->rejection_reason : null,
        ];
    }
}
