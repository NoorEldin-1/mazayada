<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Models\AuctionParticipant;
use App\Models\Payment;
use App\Services\Payments\ChargilyGateway;
use App\Services\Payments\PaymentDriver;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\PaymentService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

class ChargilyGatewayTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('documents');

        // Force the Chargily driver with a dummy key so the resolver picks it.
        config()->set('mazayada.payments.driver', 'chargily');
        config()->set('mazayada.payments.chargily.secret_key', 'test_sk_dummy');
        config()->set('mazayada.payments.chargily.webhook_secret', 'test_sk_dummy');
        config()->set('mazayada.payments.chargily.base_url', 'https://pay.chargily.net/test/api/v2');
        config()->set('mazayada.payments.chargily.payment_method', 'cib');
    }

    public function test_chargily_is_selected_when_configured(): void
    {
        $this->assertSame(PaymentDriver::CHARGILY, PaymentDriver::current());
        $this->assertInstanceOf(ChargilyGateway::class, app(PaymentGatewayInterface::class));
    }

    public function test_falls_back_to_mock_without_a_secret_key(): void
    {
        config()->set('mazayada.payments.chargily.secret_key', null);

        $this->assertSame(PaymentDriver::MOCK, PaymentDriver::current());
    }

    public function test_charge_converts_centimes_to_dinars_and_returns_checkout_url(): void
    {
        Http::fake([
            'pay.chargily.net/*/checkouts' => Http::response([
                'id' => 'chk_1',
                'checkout_url' => 'https://pay.chargily.net/payment/chk_1',
                'status' => 'pending',
            ], 200),
        ]);

        $auction = $this->makeAuction(['deposit_amount' => 100_000, 'book_price' => 0]); // 1,000 DZD
        $user = $this->makeCitizen();

        $result = app(PaymentService::class)->initiateRegistration($auction, $user);

        $this->assertSame('https://pay.chargily.net/payment/chk_1', $result['redirect_url']);
        $this->assertSame('chk_1', $result['ref']);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/checkouts')
                && $request['amount'] === 1000          // 100,000 centimes / 100
                && $request['currency'] === 'dzd'
                && $request['payment_method'] === 'cib';
        });

        // The pending payment stores the Chargily checkout id as its gateway_ref.
        $payment = Payment::where('auction_id', $auction->id)->first();
        $this->assertSame('chk_1', $payment->gateway_ref);
        $this->assertSame('chargily', $payment->gateway);
    }

    public function test_full_registration_confirms_through_chargily(): void
    {
        Http::fake([
            'pay.chargily.net/*/checkouts' => Http::response([
                'id' => 'chk_2', 'checkout_url' => 'https://pay.chargily.net/payment/chk_2', 'status' => 'pending',
            ], 200),
            'pay.chargily.net/*/checkouts/*' => Http::response([
                'id' => 'chk_2', 'status' => 'paid',
            ], 200),
        ]);

        $auction = $this->makeAuction(['deposit_amount' => 100_000, 'book_price' => 0]);
        $user = $this->makeCitizen();

        $service = app(PaymentService::class);
        $result = $service->initiateRegistration($auction, $user);

        // The gateway return / webhook arrives carrying the checkout id.
        $service->handleCallback($result['ref'], 'success');

        $payment = Payment::where('auction_id', $auction->id)->first();
        $this->assertSame(PaymentStatus::CONFIRMED, $payment->status);

        $participant = AuctionParticipant::where('auction_id', $auction->id)->where('user_id', $user->id)->first();
        $this->assertTrue($participant->deposit_paid);
        $this->assertTrue($participant->isFullyRegistered());
    }

    public function test_unpaid_checkout_is_not_confirmed_even_on_success_decision(): void
    {
        Http::fake([
            'pay.chargily.net/*/checkouts' => Http::response([
                'id' => 'chk_3', 'checkout_url' => 'https://x/chk_3', 'status' => 'pending',
            ], 200),
            'pay.chargily.net/*/checkouts/*' => Http::response([
                'id' => 'chk_3', 'status' => 'failed',
            ], 200),
        ]);

        $auction = $this->makeAuction(['deposit_amount' => 100_000, 'book_price' => 0]);
        $user = $this->makeCitizen();

        $service = app(PaymentService::class);
        $result = $service->initiateRegistration($auction, $user);

        // Even a "success" decision re-verifies with Chargily, so an unpaid
        // checkout cannot be confirmed by a tampered return URL.
        $service->handleCallback($result['ref'], 'success');

        $payment = Payment::where('auction_id', $auction->id)->first();
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
    }
}
