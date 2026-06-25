<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

class ChargilyWebhookTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    private string $secret = 'test_sk_dummy';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('documents');

        config()->set('mazayada.payments.driver', 'chargily');
        config()->set('mazayada.payments.chargily.secret_key', $this->secret);
        config()->set('mazayada.payments.chargily.webhook_secret', $this->secret);
        config()->set('mazayada.payments.chargily.base_url', 'https://pay.chargily.net/test/api/v2');
    }

    /** Create a real PENDING payment whose gateway_ref is the Chargily checkout id. */
    private function makePendingCheckout(): Payment
    {
        Http::fake([
            'pay.chargily.net/*/checkouts' => Http::response([
                'id' => 'chk_wh', 'checkout_url' => 'https://x/chk_wh', 'status' => 'pending',
            ], 200),
            'pay.chargily.net/*/checkouts/*' => Http::response([
                'id' => 'chk_wh', 'status' => 'paid',
            ], 200),
        ]);

        $auction = $this->makeAuction(['deposit_amount' => 100_000, 'book_price' => 0]);
        $user = $this->makeCitizen();
        app(PaymentService::class)->initiateRegistration($auction, $user);

        return Payment::where('gateway_ref', 'chk_wh')->firstOrFail();
    }

    private function postWebhook(array $event, string $signature): \Illuminate\Testing\TestResponse
    {
        $payload = json_encode($event);

        return $this->call('POST', '/payments/chargily/webhook', [], [], [], [
            'HTTP_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    public function test_valid_signature_confirms_the_payment(): void
    {
        $payment = $this->makePendingCheckout();

        $event = ['type' => 'checkout.paid', 'data' => ['id' => 'chk_wh', 'status' => 'paid']];
        $signature = hash_hmac('sha256', json_encode($event), $this->secret);

        $this->postWebhook($event, $signature)->assertOk();

        $this->assertSame(PaymentStatus::CONFIRMED, $payment->fresh()->status);
    }

    public function test_invalid_signature_is_rejected_and_leaves_payment_pending(): void
    {
        $payment = $this->makePendingCheckout();

        $event = ['type' => 'checkout.paid', 'data' => ['id' => 'chk_wh', 'status' => 'paid']];

        $this->postWebhook($event, 'deadbeef')->assertStatus(403);

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_failed_event_marks_the_payment_failed(): void
    {
        $payment = $this->makePendingCheckout();

        // Retrieve returns "failed" so confirm() agrees the order did not pay.
        Http::fake([
            'pay.chargily.net/*/checkouts/*' => Http::response(['id' => 'chk_wh', 'status' => 'failed'], 200),
        ]);

        $event = ['type' => 'checkout.failed', 'data' => ['id' => 'chk_wh', 'status' => 'failed']];
        $signature = hash_hmac('sha256', json_encode($event), $this->secret);

        $this->postWebhook($event, $signature)->assertOk();

        $this->assertSame(PaymentStatus::FAILED, $payment->fresh()->status);
    }
}
