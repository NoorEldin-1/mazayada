<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Chargily Pay v2 webhook — the AUTHORITATIVE payment confirmation (the browser
 * return URL is UX only). Chargily signs every request: header `signature` =
 * HMAC-SHA256(rawBody, secret_key). We verify it, then hand the checkout id to
 * PaymentService::handleCallback (for Chargily, payments.gateway_ref == the
 * checkout id, so the pending payment is found and re-verified via the API).
 *
 * Always returns 200 for an authentic-but-uninteresting event so Chargily does
 * not keep retrying; only a bad/missing signature returns 403.
 */
class ChargilyWebhookController extends Controller
{
    public function handle(Request $request, PaymentService $payments): Response
    {
        $signature = (string) $request->header('signature', '');
        $secret = (string) config('mazayada.payments.chargily.webhook_secret');
        $payload = $request->getContent();

        if ($secret === '' || $signature === '' || ! $this->validSignature($payload, $signature, $secret)) {
            Log::warning('Chargily webhook rejected: invalid signature');

            return response('invalid signature', 403);
        }

        $event = json_decode($payload, true) ?: [];
        $type = (string) ($event['type'] ?? '');
        $checkoutId = (string) ($event['data']['id'] ?? '');

        if ($checkoutId === '') {
            return response('ignored', 200);
        }

        // checkout.paid → confirm; checkout.failed / checkout.canceled → fail.
        $decision = $type === 'checkout.paid' ? 'success' : 'fail';
        $payments->handleCallback($checkoutId, $decision);

        return response('ok', 200);
    }

    private function validSignature(string $payload, string $signature, string $secret): bool
    {
        $computed = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computed, $signature);
    }
}
