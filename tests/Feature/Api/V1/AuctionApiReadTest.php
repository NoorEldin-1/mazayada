<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AuctionStatus;
use App\Models\Bid;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

class AuctionApiReadTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_lists_only_public_auctions_with_pagination_meta(): void
    {
        $active = $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        $this->makeAuction(['status' => AuctionStatus::DRAFT]);

        $this->getJson('/api/v1/auctions')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'title', 'status', 'opening_price' => ['amount', 'formatted'], 'current_price']],
                'message',
                'meta' => ['pagination' => ['current_page', 'last_page', 'per_page', 'total', 'count']],
            ])
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.id', $active->id);
    }

    public function test_index_filters_by_wilaya_and_status(): void
    {
        $this->makeAuction(['status' => AuctionStatus::ACTIVE]);

        $this->getJson('/api/v1/auctions?status=CLOSED')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 0);

        $this->getJson('/api/v1/auctions?wilaya=999')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 0);
    }

    public function test_show_returns_full_auction_in_dinars(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'opening_price' => 1_000_000]);

        $this->getJson("/api/v1/auctions/{$auction->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $auction->id)
            ->assertJsonPath('data.opening_price.amount', 10000)
            ->assertJsonPath('meta.viewer', null);
    }

    public function test_show_returns_404_for_a_draft_auction(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::DRAFT]);

        $this->getJson("/api/v1/auctions/{$auction->id}")->assertNotFound();
    }

    public function test_show_includes_viewer_context_for_a_registered_participant(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        $user = $this->makeCitizen();
        $this->makeParticipant($auction, $user);

        Sanctum::actingAs($user, ['access']);

        $this->getJson("/api/v1/auctions/{$auction->id}")
            ->assertOk()
            ->assertJsonPath('meta.viewer.is_participant', true)
            ->assertJsonPath('meta.viewer.can_bid', true);
    }

    public function test_latest_bids_returns_aliases_not_identities(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'opening_price' => 1_000_000]);
        $user = $this->makeCitizen(['email' => 'secret-bidder@example.test']);
        Bid::create([
            'auction_id' => $auction->id,
            'user_id' => $user->id,
            'amount' => 1_500_000,
            'bid_time' => now(),
            'is_valid' => true,
        ]);

        $response = $this->getJson("/api/v1/auctions/{$auction->id}/bids")
            ->assertOk()
            ->assertJsonStructure(['data' => [['amount' => ['amount', 'formatted'], 'bidder_alias', 'bid_time']]])
            ->assertJsonPath('data.0.amount.amount', 15000)
            ->assertJsonPath('meta.current_price', 15000);

        // The real identity must never leak.
        $response->assertJsonMissing(['email' => 'secret-bidder@example.test']);
        $this->assertStringNotContainsString($user->id, $response->getContent());
    }

    public function test_price_endpoint_returns_a_snapshot(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'opening_price' => 1_000_000]);

        $this->getJson("/api/v1/auctions/{$auction->id}/price")
            ->assertOk()
            ->assertJsonPath('data.current_price', 10000)
            ->assertJsonStructure(['data' => ['current_price', 'bid_count', 'status', 'end_time', 'is_biddable']]);
    }

    public function test_entity_scope_does_not_hide_public_auctions_from_a_citizen_token(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/auctions')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.id', $auction->id);
    }
}
