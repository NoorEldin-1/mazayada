<?php

namespace Tests\Feature;

use App\Enums\AuctionStatus;
use App\Events\AuctionClosed;
use App\Models\Bid;
use App\Notifications\AuctionEventNotification;
use App\Services\AuctionService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

class AuctionCloseTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('documents');
    }

    public function test_close_picks_highest_bidder_and_broadcasts(): void
    {
        Event::fake([AuctionClosed::class]);

        $auction = $this->makeAuction(['end_time' => now()->subMinute()]);
        $high = $this->makeCitizen();
        $low = $this->makeCitizen();
        $this->makeParticipant($auction, $high);
        $this->makeParticipant($auction, $low);

        Bid::create(['auction_id' => $auction->id, 'user_id' => $low->id, 'amount' => 1_500_000, 'bid_time' => now()->subMinutes(3), 'is_valid' => true]);
        Bid::create(['auction_id' => $auction->id, 'user_id' => $high->id, 'amount' => 2_000_000, 'bid_time' => now()->subMinutes(2), 'is_valid' => true]);

        app(AuctionService::class)->close($auction);

        $auction->refresh();
        $this->assertSame(AuctionStatus::CLOSED, $auction->status);
        $this->assertSame($high->id, $auction->winner_user_id);
        $this->assertSame(2_000_000, (int) $auction->final_price);
        $this->assertNotNull($auction->closed_at);
        Event::assertDispatched(AuctionClosed::class);

        // §6 — an award document is generated for the winner.
        $this->assertDatabaseHas('documents', ['type' => 'AWARD', 'auction_id' => $auction->id]);
    }

    public function test_original_owner_wins_a_tie(): void
    {
        Event::fake([AuctionClosed::class]);

        $owner = $this->makeCitizen();
        $other = $this->makeCitizen();

        $auction = $this->makeAuction(['end_time' => now()->subMinute(), 'original_owner_nin' => $owner->nin]);
        $this->makeParticipant($auction, $owner, ['is_original_owner' => true]);
        $this->makeParticipant($auction, $other);

        // Same top amount; the OTHER bid is earlier, but the original owner wins the tie.
        Bid::create(['auction_id' => $auction->id, 'user_id' => $other->id, 'amount' => 2_000_000, 'bid_time' => now()->subMinutes(5), 'is_valid' => true]);
        Bid::create(['auction_id' => $auction->id, 'user_id' => $owner->id, 'amount' => 2_000_000, 'bid_time' => now()->subMinutes(2), 'is_valid' => true]);

        app(AuctionService::class)->close($auction);

        $this->assertSame($owner->id, $auction->fresh()->winner_user_id);
    }

    public function test_close_notifies_winner_and_losers(): void
    {
        Notification::fake();

        $auction = $this->makeAuction(['end_time' => now()->subMinute()]);
        $winner = $this->makeCitizen();
        $loser = $this->makeCitizen();
        $this->makeParticipant($auction, $winner);
        $this->makeParticipant($auction, $loser);

        Bid::create(['auction_id' => $auction->id, 'user_id' => $winner->id, 'amount' => 2_000_000, 'bid_time' => now()->subMinutes(2), 'is_valid' => true]);

        app(AuctionService::class)->close($auction);

        Notification::assertSentTo($winner, AuctionEventNotification::class);
        Notification::assertSentTo($loser, AuctionEventNotification::class);
    }
}
