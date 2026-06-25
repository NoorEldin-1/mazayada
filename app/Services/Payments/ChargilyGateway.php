<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Chargily Pay v2 integration — EDAHABIA (Algérie Poste) / CIB (SATIM). Spec §7.
 *
 * TEST mode by default: the hosted page at checkout_url is a REAL Chargily
 * checkout; only the credentials and the money are test. Going live is an
 * ENV-ONLY change (CHARGILY_BASE_URL + CHARGILY_SECRET_KEY → live values). This
 * driver is selected when PaymentDriver::current() === 'chargily', i.e.
 * PAYMENTS_DRIVER=chargily AND a CHARGILY_SECRET_KEY is configured.
 *
 * MONEY BOUNDARY: our Payment::$amount is in centimes (1 DZD = 100). Chargily's
 * `amount` is in WHOLE dinars with a 50 DZD minimum, so we divide by 100 here.
 *
 * REFUNDS: Chargily Pay v2 exposes NO refund endpoint. refund() is recorded
 * locally only; the real money return (live mode) is done out-of-band from the
 * Chargily dashboard. In test mode no money moves, so this is fully transparent.
 */
class ChargilyGateway implements PaymentGatewayInterface
{
    /** Chargily's smallest accepted checkout amount, in whole dinars. */
    private const MIN_DZD = 50;

    public function charge(Payment $payment, array $context = []): GatewayResult
    {
        $amountDzd = intdiv((int) $payment->amount, 100); // centimes → DZD

        if ($amountDzd < self::MIN_DZD) {
            // Below Chargily's floor — should not happen for real auction fees.
            Log::warning('Chargily charge below minimum', ['payment' => $payment->id, 'dzd' => $amountDzd]);
            throw new RuntimeException(__('payments.gateway_error'));
        }

        $response = $this->client()->post('/checkouts', [
            'amount' => $amountDzd,
            'currency' => 'dzd',
            'payment_method' => (string) config('mazayada.payments.chargily.payment_method', 'cib'),
            // Browser return URLs carry OUR payment id; the gateway re-verifies on
            // return, so a tampered query param can never confirm an unpaid order.
            'success_url' => route('payments.callback', ['ref' => $payment->id, 'decision' => 'success']),
            'failure_url' => route('payments.callback', ['ref' => $payment->id, 'decision' => 'fail']),
            // Authoritative confirmation channel (server-to-server, signed).
            'webhook_endpoint' => route('payments.chargily.webhook'),
            'description' => $context['description'] ?? 'Mazayada payment',
            'locale' => in_array(app()->getLocale(), ['ar', 'fr', 'en'], true) ? app()->getLocale() : 'ar',
            'metadata' => [
                'payment_id' => (string) $payment->id,
                'purpose' => (string) data_get($payment->payable_meta, 'purpose', ''),
            ],
        ]);

        $data = $response->json() ?? [];

        if (! $response->successful() || ! isset($data['id'], $data['checkout_url'])) {
            Log::error('Chargily checkout failed', ['status' => $response->status(), 'body' => $data]);
            throw new RuntimeException(__('payments.gateway_error'));
        }

        return new GatewayResult(
            status: 'PENDING',
            ref: (string) $data['id'],
            redirectUrl: (string) $data['checkout_url'],
            raw: $data,
        );
    }

    public function confirm(string $gatewayRef): GatewayResult
    {
        $response = $this->client()->get('/checkouts/'.$gatewayRef);
        $data = $response->json() ?? [];

        // v2 checkout statuses: pending | paid | failed | canceled | expired.
        $confirmed = ($data['status'] ?? null) === 'paid';

        return new GatewayResult(
            status: $confirmed ? 'CONFIRMED' : 'FAILED',
            ref: $gatewayRef,
            raw: $data,
        );
    }

    public function refund(Payment $payment): GatewayResult
    {
        // Chargily Pay v2 has no refund API. Record the intent locally; the actual
        // money return (live mode) is performed manually from the dashboard.
        Log::info('Chargily refund recorded locally (no API)', [
            'payment' => $payment->id,
            'amount_centimes' => (int) $payment->amount,
        ]);

        return new GatewayResult(
            status: 'REFUNDED',
            ref: (string) ($payment->gateway_ref ?? $payment->id),
            raw: ['simulated' => true, 'reason' => 'chargily_no_refund_api'],
        );
    }

    private function client()
    {
        return Http::baseUrl(rtrim((string) config('mazayada.payments.chargily.base_url'), '/'))
            ->withToken((string) config('mazayada.payments.chargily.secret_key'))
            ->acceptJson()
            ->timeout(20);
    }
}
