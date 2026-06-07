<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Real CIBWeb / SATIM REST integration (spec §7.2) — SKELETON.
 *
 * The request/response mapping follows SATIM's hosted-payment API
 * (register.do / getOrderStatus.do / refund.do, currency 012 = DZD). Wired but
 * inert until real credentials are configured; it is selected only when
 * setting('payments.mock') is false. Until then the platform runs on
 * MockPaymentGateway.
 *
 * IMPORTANT (spec §7.4): credentials come from env only; never store card data.
 */
class CibWebGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment, array $context = []): GatewayResult
    {
        $response = $this->client()->asForm()->post($this->endpoint('register.do'), [
            'userName' => config('mazayada.payments.cibweb.username'),
            'password' => config('mazayada.payments.cibweb.password'),
            'orderNumber' => $payment->id,
            'amount' => $payment->amount, // centimes
            'currency' => config('mazayada.payments.cibweb.currency', '012'),
            'returnUrl' => $context['return_url'] ?? route('payments.callback'),
            'description' => $context['description'] ?? 'Mazayada payment',
            'language' => app()->getLocale() === 'fr' ? 'FR' : 'AR',
        ]);

        $data = $response->json() ?? [];

        if (! isset($data['formUrl'], $data['orderId'])) {
            Log::error('CibWeb register failed', ['body' => $data]);
            throw new RuntimeException(__('payments.gateway_error'));
        }

        return new GatewayResult(
            status: 'PENDING',
            ref: (string) $data['orderId'],
            redirectUrl: (string) $data['formUrl'],
            raw: $data,
        );
    }

    public function confirm(string $gatewayRef): GatewayResult
    {
        $response = $this->client()->asForm()->post($this->endpoint('getOrderStatus.do'), [
            'userName' => config('mazayada.payments.cibweb.username'),
            'password' => config('mazayada.payments.cibweb.password'),
            'orderId' => $gatewayRef,
        ]);

        $data = $response->json() ?? [];

        // SATIM: orderStatus === 2 means the payment is fully approved.
        $confirmed = (int) ($data['orderStatus'] ?? -1) === 2;

        return new GatewayResult(
            status: $confirmed ? 'CONFIRMED' : 'FAILED',
            ref: $gatewayRef,
            raw: $data,
        );
    }

    public function refund(Payment $payment): GatewayResult
    {
        $response = $this->client()->asForm()->post($this->endpoint('refund.do'), [
            'userName' => config('mazayada.payments.cibweb.username'),
            'password' => config('mazayada.payments.cibweb.password'),
            'orderId' => $payment->gateway_ref,
            'amount' => $payment->amount,
        ]);

        $data = $response->json() ?? [];
        $ok = (int) ($data['errorCode'] ?? -1) === 0;

        return new GatewayResult(
            status: $ok ? 'REFUNDED' : 'FAILED',
            ref: (string) $payment->gateway_ref,
            raw: $data,
        );
    }

    private function client()
    {
        return Http::baseUrl((string) config('mazayada.payments.cibweb.base_url'))
            ->timeout(20)
            ->acceptJson();
    }

    private function endpoint(string $path): string
    {
        return '/'.ltrim($path, '/');
    }
}
