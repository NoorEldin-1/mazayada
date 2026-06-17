<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Payment;
use App\Support\Api\FormatsMoney;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A payment row (deposit / entry fee / book / final payment). Amount in dinars.
 *
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    use FormatsMoney;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->payment_type?->value,
            'amount' => $this->money($this->amount),
            'status' => $this->status?->value,
            'gateway_ref' => $this->gateway_ref,
            'due_at' => $this->due_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
