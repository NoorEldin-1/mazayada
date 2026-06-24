<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AuctionStatus;
use App\Models\Bid;
use App\Models\UserNotification;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

class DashboardApiTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_dashboard_returns_stats_and_recent_data(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        $user = $this->makeCitizen();
        $this->makeParticipant($auction, $user);
        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['stats' => ['active', 'won', 'total_participations'], 'kyc_status', 'won_auctions', 'recent_notifications'],
            ])
            ->assertJsonPath('data.stats.active', 1)
            ->assertJsonPath('data.stats.total_participations', 1);
    }

    public function test_my_auctions_groups_by_tab(): void
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        $user = $this->makeCitizen();
        $this->makeParticipant($auction, $user);
        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/my-auctions?tab=active')
            ->assertOk()
            ->assertJsonPath('meta.tab', 'active')
            ->assertJsonPath('meta.counts.active', 1)
            ->assertJsonPath('data.0.id', $auction->id);
    }

    public function test_profile_show_and_update(): void
    {
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->putJson('/api/v1/profile', ['phone' => '0561234567', 'profession' => 'Engineer'])
            ->assertOk()
            ->assertJsonPath('data.phone', '0561234567');

        $this->assertSame('0561234567', $user->fresh()->phone);
    }

    public function test_profile_update_rejects_invalid_phone(): void
    {
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $this->putJson('/api/v1/profile', ['phone' => '123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_appeal_store_requires_eligibility(): void
    {
        // An appeal is filed against a CLOSED auction the user took part in with
        // at least one valid bid; the client only ever sees the public states.
        $auction = $this->makeAuction(['status' => AuctionStatus::CLOSED, 'closed_at' => now()]);
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        // Not a participant / no bid -> rejected.
        $this->postJson("/api/v1/auctions/{$auction->id}/appeals", [
            'subject' => 'Test', 'reason' => 'Reason',
        ])->assertStatus(422);

        // Eligible: registered participant with a valid bid.
        $this->makeParticipant($auction, $user);
        Bid::create([
            'auction_id' => $auction->id, 'user_id' => $user->id,
            'amount' => 1_100_000, 'bid_time' => now(), 'is_valid' => true,
        ]);

        $this->postJson("/api/v1/auctions/{$auction->id}/appeals", [
            'subject' => 'Test', 'reason' => 'Reason',
        ])->assertCreated()->assertJsonPath('data.status', 'PENDING');
    }

    public function test_notifications_list_and_mark_read(): void
    {
        $user = $this->makeCitizen();
        $other = $this->makeCitizen();
        $note = UserNotification::record($user->id, 'Title', 'Body');
        $othersNote = UserNotification::record($other->id, 'X', 'Y');

        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'title', 'is_read']], 'meta' => ['unread_count', 'pagination']])
            ->assertJsonPath('meta.unread_count', 1);

        // Cannot mark another user's notification.
        $this->postJson("/api/v1/notifications/{$othersNote->id}/read")->assertForbidden();

        $this->postJson("/api/v1/notifications/{$note->id}/read")->assertOk();
        $this->assertTrue($note->fresh()->is_read);
    }
}
