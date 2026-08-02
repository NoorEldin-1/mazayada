<?php

namespace Tests\Feature;

use App\Enums\AuctionStatus;
use App\Models\Wilaya;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

/**
 * The homepage advertised hand-written figures ("+2400 auctions", "5 government
 * bodies", "+320" per institution) that contradicted the real catalogue — on a
 * platform whose stated value is full transparency. Every headline number must
 * now come from the database.
 */
class HomePageStatsTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_hero_statistics_are_live_counts(): void
    {
        $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        $this->makeAuction(['status' => AuctionStatus::PUBLISHED]);

        $response = $this->get(route('home'))->assertOk();

        $response->assertDontSee('+2400');
        $response->assertDontSee('+320');

        // 2 public auctions, 1 entity and the real wilaya count.
        $response->assertSee('>2</div>', false);
        $response->assertSee('>'.number_format(Wilaya::count()).'</div>', false);
    }

    public function test_institutions_strip_lists_real_entities(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($auction->entity->name);
    }
}
