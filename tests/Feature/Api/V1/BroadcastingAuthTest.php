<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AuctionStatus;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

/**
 * The Sanctum-guarded broadcasting auth endpoint (api/broadcasting/auth) used by
 * the Flutter client to authorize private Reverb channels with a bearer token.
 */
class BroadcastingAuthTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-auction.x.user.y',
            'socket_id' => '1234.5678',
        ])->assertUnauthorized();
    }

    public function test_participant_can_authorize_their_private_channel(): void
    {
        $this->usePusherBroadcaster();

        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        $user = $this->makeCitizen();
        $this->makeParticipant($auction, $user);
        Sanctum::actingAs($user, ['access']);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "private-auction.{$auction->id}.user.{$user->id}",
            'socket_id' => '1234.5678',
        ])
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_non_participant_cannot_authorize_the_private_channel(): void
    {
        $this->usePusherBroadcaster();

        $auction = $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        $user = $this->makeCitizen(); // no participation row
        Sanctum::actingAs($user, ['access']);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "private-auction.{$auction->id}.user.{$user->id}",
            'socket_id' => '1234.5678',
        ])->assertForbidden();
    }

    /**
     * Switch the default broadcaster to a (dummy-credentialed) Pusher-protocol
     * driver so channel authorization rules are actually enforced — the test
     * 'null' broadcaster performs no authorization. Channels are re-registered on
     * the new driver.
     */
    private function usePusherBroadcaster(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher' => [
                'driver' => 'pusher',
                'key' => 'test-key',
                'secret' => 'test-secret',
                'app_id' => 'test-app',
                'options' => ['cluster' => 'mt1', 'useTLS' => false],
            ],
        ]);

        require base_path('routes/channels.php');
    }
}
