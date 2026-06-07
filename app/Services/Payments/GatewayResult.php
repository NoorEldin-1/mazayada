<?php

namespace App\Services\Payments;

/**
 * Normalized result returned by every payment gateway driver. `status` mirrors
 * the App\Enums\PaymentStatus values relevant to a gateway round-trip.
 */
final readonly class GatewayResult
{
    public function __construct(
        public string $status,            // PENDING | CONFIRMED | FAILED | REFUNDED
        public string $ref,               // gateway order/transaction reference
        public ?string $redirectUrl = null,
        public array $raw = [],
    ) {}

    public function isConfirmed(): bool
    {
        return $this->status === 'CONFIRMED';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'REFUNDED';
    }
}
