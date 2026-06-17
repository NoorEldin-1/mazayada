<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AuctionStatus;
use App\Enums\KycStatus;
use App\Events\BidPlaced;
use App\Models\Bid;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

class BiddingApiTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_place_bid_stores_centimes_and_dispatches_event(): void
    {
        Event::fake([BidPlaced::class]);

        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'opening_price' => 1_000_000]);
        $user = $this->makeCitizen();
        $this->makeParticipant($auction, $user);
        Sanctum::actingAs($user, ['access']);

        $this->postJson("/api/v1/auctions/{$auction->id}/bid", ['amount' => 15000])
            ->assertCreated()
            ->assertJsonPath('data.bid.amount.amount', 15000)
            ->assertJsonPath('data.current_price', 15000);

        // Dinars -> centimes at the boundary.
        $this->assertDatabaseHas('bids', ['auction_id' => $auction->id, 'user_id' => $user->id, 'amount' => 1_500_000]);
        Event::assertDispatched(BidPlaced::class);
    }

    public function test_bid_below_current_price_returns_422(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'opening_price' => 1_000_000]);
        $user = $this->makeCitizen();
        $this->makeParticipant($auction, $user);
        Sanctum::actingAs($user, ['access']);

        $this->postJson("/api/v1/auctions/{$auction->id}/bid", ['amount' => 5000])
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');
    }

    public function test_unregistered_user_cannot_bid(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'opening_price' => 1_000_000]);
        $user = $this->makeCitizen(); // no participant row
        Sanctum::actingAs($user, ['access']);

        $this->postJson("/api/v1/auctions/{$auction->id}/bid", ['amount' => 15000])
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');

        $this->assertSame(0, Bid::where('auction_id', $auction->id)->count());
    }

    public function test_kyc_incomplete_user_is_blocked_with_403(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        $user = $this->makeCitizen(['kyc_status' => KycStatus::PENDING, 'kyc_completed_at' => null]);
        Sanctum::actingAs($user, ['access']);

        $this->postJson("/api/v1/auctions/{$auction->id}/bid", ['amount' => 15000])
            ->assertForbidden();
    }

    public function test_bidding_is_rate_limited(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'opening_price' => 1_000_000]);
        $user = $this->makeCitizen();
        $this->makeParticipant($auction, $user);
        Sanctum::actingAs($user, ['access']);

        // 10/min is the configured cap (BID_MAX_PER_MINUTE in phpunit.xml).
        for ($i = 1; $i <= 10; $i++) {
            $this->postJson("/api/v1/auctions/{$auction->id}/bid", ['amount' => 10000 + $i * 1000])
                ->assertCreated();
        }

        $this->postJson("/api/v1/auctions/{$auction->id}/bid", ['amount' => 99000])
            ->assertStatus(429);
    }
}
