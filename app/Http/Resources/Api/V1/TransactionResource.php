<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Payment;
use App\Support\Api\FormatsMoney;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A financial transaction (payment) with its auction context.
 *
 * @mixin Payment
 */
class TransactionResource extends JsonResource
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
            'type_label' => $this->payment_type?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'amount' => $this->money($this->amount),
            'auction' => $this->whenLoaded('auction', fn () => $this->auction ? [
                'id' => $this->auction->id,
                'title' => $this->auction->localizedTitle(),
                'category_name' => $this->auction->category?->name,
                'wilaya_name' => $this->auction->wilaya?->name,
            ] : null),
            'gateway' => $this->gateway,
            'gateway_ref' => $this->gateway_ref,
            'created_at' => $this->created_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'forfeited_at' => $this->forfeited_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
        ];
    }
}
