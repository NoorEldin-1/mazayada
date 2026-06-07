<?php

namespace Tests\Feature;

use App\Enums\AuctionStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

class DepositSettlementTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('documents');
    }

    public function test_settlement_refunds_losers_and_forfeits_defaulting_winner(): void
    {
        $winner = $this->makeCitizen();
        $loser = $this->makeCitizen();

        // Closed 9 days ago — past the 8-day movable deadline.
        $auction = $this->makeAuction([
            'status' => AuctionStatus::CLOSED,
            'end_time' => now()->subDays(9),
            'closed_at' => now()->subDays(9),
            'winner_user_id' => $winner->id,
            'final_price' => 2_000_000,
        ]);
        $this->makeParticipant($auction, $winner);
        $this->makeParticipant($auction, $loser);

        $winnerDeposit = $this->deposit($auction, $winner);
        $loserDeposit = $this->deposit($auction, $loser);

        $this->artisan('auctions:settle-deposits')->assertOk();

        // Winner defaulted (no final payment) → forfeited + blacklisted.
        $this->assertSame(PaymentStatus::FORFEITED, $winnerDeposit->fresh()->status);
        $this->assertTrue($winner->fresh()->is_blacklisted);

        // Loser refunded.
        $this->assertSame(PaymentStatus::REFUNDED, $loserDeposit->fresh()->status);

        $this->assertNotNull($auction->fresh()->settled_at);

        // Idempotent — a second run changes nothing.
        $this->artisan('auctions:settle-deposits')->assertOk();
        $this->assertSame(PaymentStatus::REFUNDED, $loserDeposit->fresh()->status);
    }

    public function test_settlement_waits_until_deadline(): void
    {
        $winner = $this->makeCitizen();
        $auction = $this->makeAuction([
            'status' => AuctionStatus::CLOSED,
            'closed_at' => now()->subDay(), // deadline is 8 days out — not yet
            'winner_user_id' => $winner->id,
            'final_price' => 2_000_000,
        ]);
        $this->makeParticipant($auction, $winner);
        $this->deposit($auction, $winner);

        $this->artisan('auctions:settle-deposits')->assertOk();

        $this->assertNull($auction->fresh()->settled_at);
    }

    private function deposit($auction, $user): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'auction_id' => $auction->id,
            'payment_type' => PaymentType::DEPOSIT,
            'amount' => 100_000,
            'status' => PaymentStatus::CONFIRMED,
            'gateway' => 'mock',
            'gateway_ref' => 'MOCK-'.$user->id,
            'confirmed_at' => now()->subDays(9),
        ]);
    }
}
