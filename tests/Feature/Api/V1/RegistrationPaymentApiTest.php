<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AuctionStatus;
use App\Enums\PaymentStatus;
use App\Models\AuctionParticipant;
use App\Models\InspectionQuestion;
use App\Models\Payment;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

class RegistrationPaymentApiTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('documents');
    }

    public function test_buy_book_returns_a_gateway_redirect(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'book_price' => 300_000]);
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $this->postJson("/api/v1/auctions/{$auction->id}/buy-book")
            ->assertOk()
            ->assertJsonStructure(['data' => ['redirect_url', 'ref']]);

        $this->assertSame(1, Payment::where('auction_id', $auction->id)->where('status', PaymentStatus::PENDING)->count());
    }

    public function test_start_registration_returns_a_gateway_redirect(): void
    {
        // A free book lets registration proceed directly, charging only the deposit.
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'deposit_amount' => 100_000, 'book_price' => 0]);
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/register")
            ->assertOk()
            ->assertJsonStructure(['data' => ['redirect_url', 'ref']]);

        $this->assertStringContainsString('ref=', $response->json('data.redirect_url'));
        $this->assertSame(1, Payment::where('auction_id', $auction->id)->where('status', PaymentStatus::PENDING)->count());
    }

    public function test_register_without_buying_priced_book_returns_422(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'book_price' => 300_000]);
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $this->postJson("/api/v1/auctions/{$auction->id}/register")
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_callback_confirms_payment_and_status_reflects_it(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'deposit_amount' => 100_000, 'book_price' => 0]);
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $ref = $this->postJson("/api/v1/auctions/{$auction->id}/register")->json('data.ref');

        // Public gateway return.
        $this->getJson("/api/v1/payments/callback?ref={$ref}&decision=success")
            ->assertOk()
            ->assertJsonPath('data.confirmed', true);

        $participant = AuctionParticipant::where('auction_id', $auction->id)->where('user_id', $user->id)->first();
        $this->assertTrue($participant->deposit_paid);

        // Authenticated status poll.
        $this->getJson("/api/v1/payments/{$ref}/status")
            ->assertOk()
            ->assertJsonPath('data.payments.0.status', PaymentStatus::CONFIRMED->value);
    }

    public function test_final_payment_requires_the_winner(): void
    {
        $winner = $this->makeCitizen();
        $auction = $this->makeAuction([
            'status' => AuctionStatus::CLOSED,
            'winner_user_id' => $winner->id,
            'final_price' => 2_000_000,
            'closed_at' => now(),
        ]);
        $other = $this->makeCitizen();
        Sanctum::actingAs($other, ['access']);

        $this->postJson("/api/v1/auctions/{$auction->id}/final-payment")
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_ask_question_creates_a_pending_public_question(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $this->postJson("/api/v1/auctions/{$auction->id}/questions", ['question' => 'هل المركبة تعمل؟'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'PENDING');

        $this->assertSame(1, InspectionQuestion::where('auction_id', $auction->id)->where('user_id', $user->id)->count());
    }
}
