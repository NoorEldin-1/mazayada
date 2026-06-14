<?php

namespace Tests\Feature;

use App\Enums\AuctionStatus;
use App\Enums\InspectionQuestionStatus;
use App\Models\InspectionQuestion;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

/**
 * Guardrail: the public auction page must render in every branch (guest,
 * authenticated non-participant, closed-with-winner). Catches Blade parse
 * errors that view:cache alone may miss at runtime.
 */
class AuctionShowPageTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_live_auction_renders_for_guest(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE]);

        $this->get(route('auctions.show', $auction))->assertOk();
    }

    public function test_live_auction_renders_for_authenticated_citizen(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        $user = $this->makeCitizen();

        $this->actingAs($user)->get(route('auctions.show', $auction))->assertOk();
    }

    public function test_auction_with_photos_renders_gallery_carousel(): void
    {
        $auction = $this->makeAuction([
            'status' => AuctionStatus::ACTIVE,
            'photos' => 'auctions/x/a.jpg;auctions/x/b.jpg;auctions/x/c.jpg',
        ]);

        $response = $this->get(route('auctions.show', $auction))->assertOk();
        // Carousel scaffold (Swiper hero) + every uploaded photo URL is emitted.
        $response->assertSee('data-gallery', false);
        $response->assertSee('data-hero', false);
        $response->assertSee('/storage/auctions/x/a.jpg', false);
        $response->assertSee('/storage/auctions/x/c.jpg', false);
    }

    public function test_listing_card_renders_cover_photo(): void
    {
        $this->makeAuction([
            'status' => AuctionStatus::ACTIVE,
            'photos' => 'auctions/x/cover.jpg;auctions/x/b.jpg',
        ]);

        $this->get(route('auctions.index'))
            ->assertOk()
            ->assertSee('/storage/auctions/x/cover.jpg', false);
    }

    public function test_inspection_tab_shows_qa_and_ask_form(): void
    {
        $auction = $this->makeAuction([
            'status' => AuctionStatus::ACTIVE,
            'inspection_location' => 'مستودع البلدية',
        ]);
        $asker = $this->makeCitizen();

        InspectionQuestion::create([
            'auction_id' => $auction->id,
            'user_id' => $asker->id,
            'question' => 'هل المحرك يعمل؟',
            'answer' => 'نعم، المحرك بحالة جيدة.',
            'status' => InspectionQuestionStatus::ANSWERED,
            'is_public' => true,
        ]);

        // A KYC-complete citizen sees the inspection tab, the answered Q&A, and the ask form.
        $response = $this->actingAs($this->makeCitizen())
            ->get(route('auctions.show', $auction))
            ->assertOk();

        $response->assertSee(__('auctions.show.tab_inspection'));
        $response->assertSee('هل المحرك يعمل؟');
        $response->assertSee('نعم، المحرك بحالة جيدة.');
        $response->assertSee('مستودع البلدية');
        $response->assertSee(route('auctions.questions', $auction), false);
    }

    public function test_expired_active_auction_closes_on_view_and_hides_bid_form(): void
    {
        // The clock ran out but the auctions:close cron hasn't run yet — the
        // dead-zone that used to render a live-looking (but doomed) bid form.
        $auction = $this->makeAuction([
            'status' => AuctionStatus::ACTIVE,
            'start_time' => now()->subHours(2),
            'end_time' => now()->subMinute(),
        ]);

        $response = $this->get(route('auctions.show', $auction))->assertOk();

        // Lazy close-on-view finalised the auction for this very visitor...
        $this->assertSame(AuctionStatus::CLOSED, $auction->fresh()->status);
        // ...so the live badge is gone and the canonical closed panel renders.
        $response->assertSee(__('auctions.show.closed'));
        $response->assertDontSee(__('auctions.live'));
    }

    public function test_closed_auction_with_winner_renders(): void
    {
        $winner = $this->makeCitizen();
        $auction = $this->makeAuction([
            'status' => AuctionStatus::CLOSED,
            'winner_user_id' => $winner->id,
            'final_price' => 2_000_000,
            'closed_at' => now(),
        ]);

        $this->actingAs($winner)->get(route('auctions.show', $auction))->assertOk();
    }
}
