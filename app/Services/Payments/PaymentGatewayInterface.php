<?php

namespace App\Services\Payments;

use App\Models\Payment;

/**
 * Contract for the national payment gateway (CIBWeb / SATIM — spec §7) and its
 * mock. The concrete binding is chosen in AppServiceProvider based on
 * setting('payments.mock').
 */
interface PaymentGatewayInterface
{
    /**
     * Register a payment order and obtain a redirect URL to the hosted payment
     * page. The returned ref must be persisted to confirm the order later.
     *
     * @param  array{return_url?: string, description?: string}  $context
     */
    public function charge(Payment $payment, array $context = []): GatewayResult;

    /** Verify/capture the order status after the user returns from the gateway. */
    public function confirm(string $gatewayRef): GatewayResult;

    /** Refund a previously confirmed payment (e.g. a losing bidder's deposit). */
    public function refund(Payment $payment): GatewayResult;
}
