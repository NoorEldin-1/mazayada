<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * In-process mock of the CIBWeb gateway (Phase 1 / dev / testing). It never
 * talks to a real PSP: charge() returns a redirect to our own callback route
 * that simulates a successful (or failed) payment, and confirm()/refund()
 * resolve immediately. Active whenever setting('payments.mock') is true
 * (default — see .env CIBWEB_MOCK=true).
 */
class MockPaymentGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment, array $context = []): GatewayResult
    {
        $ref = 'MOCK-'.Str::upper(Str::random(12));

        // Redirect the user to our own callback, simulating the PSP return.
        $redirect = route('payments.callback', [
            'ref' => $ref,
            'decision' => 'success',
        ]);

        return new GatewayResult(
            status: 'PENDING',
            ref: $ref,
            redirectUrl: $redirect,
            raw: ['mock' => true, 'amount' => $payment->amount],
        );
    }

    public function confirm(string $gatewayRef): GatewayResult
    {
        // The mock treats every returned reference as paid.
        return new GatewayResult(
            status: 'CONFIRMED',
            ref: $gatewayRef,
            raw: ['mock' => true],
        );
    }

    public function refund(Payment $payment): GatewayResult
    {
        return new GatewayResult(
            status: 'REFUNDED',
            ref: $payment->gateway_ref ?? ('MOCK-REFUND-'.Str::upper(Str::random(8))),
            raw: ['mock' => true],
        );
    }
}
