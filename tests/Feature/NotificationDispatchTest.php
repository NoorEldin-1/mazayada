<?php

namespace Tests\Feature;

use App\Services\NotificationService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

class NotificationDispatchTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_event_writes_an_in_app_notification(): void
    {
        $user = $this->makeCitizen();
        $auction = $this->makeAuction(['winner_user_id' => $user->id, 'final_price' => 2_000_000, 'closed_at' => now()]);
        $auction->setRelation('winner', $user);

        app(NotificationService::class)->auctionWon($user, $auction);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'channel' => 'IN_APP',
            'is_read' => false,
        ]);
    }

    public function test_user_can_mark_notification_read(): void
    {
        $user = $this->makeCitizen();
        $auction = $this->makeAuction();
        app(NotificationService::class)->auctionLost($user, $auction);

        $notification = $user->userNotifications()->firstOrFail();
        $this->assertFalse($notification->is_read);

        $this->actingAs($user)
            ->post(route('citizen.notifications.read', $notification))
            ->assertRedirect();

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_user_cannot_mark_another_users_notification_read(): void
    {
        $owner = $this->makeCitizen();
        $stranger = $this->makeCitizen();
        $auction = $this->makeAuction();
        app(NotificationService::class)->auctionLost($owner, $auction);
        $notification = $owner->userNotifications()->firstOrFail();

        $this->actingAs($stranger)
            ->post(route('citizen.notifications.read', $notification))
            ->assertForbidden();
    }
}
