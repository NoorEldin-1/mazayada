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

    public function test_index_status_token_live_matches_active_and_extended(): void
    {
        $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        $this->makeAuction(['status' => AuctionStatus::EXTENDED]);
        $this->makeAuction(['status' => AuctionStatus::CLOSED]);

        $this->getJson('/api/v1/auctions?status[]=live')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 2);

        $this->getJson('/api/v1/auctions?status[]=closed')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_index_filters_by_price_range_in_dinars(): void
    {
        // opening_price is centimes: 500_000c = 5_000 DA, 3_000_000c = 30_000 DA.
        $cheap = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'opening_price' => 500_000]);
        $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'opening_price' => 3_000_000]);

        $this->getJson('/api/v1/auctions?price_min=1000&price_max=10000')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.id', $cheap->id);
    }

    public function test_index_filters_by_commercial_register_requirement(): void
    {
        $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'requires_commerce_register' => true]);
        $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'requires_commerce_register' => false]);

        $this->getJson('/api/v1/auctions?requires_cr=1')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1);

        // "0" is a meaningful value, not an empty filter.
        $this->getJson('/api/v1/auctions?requires_cr=0')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_search_requires_two_chars_and_matches_title(): void
    {
        $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'title_ar' => 'فيلا فاخرة', 'title_en' => 'Luxury villa']);

        // Under 2 chars → empty.
        $this->getJson('/api/v1/auctions/search?q=v')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/auctions/search?q=villa')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'current_price' => ['amount', 'formatted']]]]);
    }

    public function test_filters_endpoint_returns_reference_data(): void
    {
        $this->makeAuction(['status' => AuctionStatus::ACTIVE]);

        $this->getJson('/api/v1/auctions/filters')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['categories', 'wilayas', 'communes', 'statuses', 'types', 'asset_classes', 'conditions', 'sorts'],
            ])
            ->assertJsonPath('data.statuses', ['upcoming', 'live', 'closed']);
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
