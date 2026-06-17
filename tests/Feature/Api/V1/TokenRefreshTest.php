<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

class TokenRefreshTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_refresh_rotates_the_pair_and_invalidates_the_old_tokens(): void
    {
        $user = $this->makeCitizen();
        ['access_token' => $access1, 'refresh_token' => $refresh1] = $this->loginTokens($user);

        $rotated = $this->withToken($refresh1)->postJson('/api/v1/auth/refresh')
            ->assertOk()
            ->assertJsonStructure(['data' => ['tokens' => ['access_token', 'refresh_token']]])
            ->json('data.tokens');

        // New access token works.
        $this->withToken($rotated['access_token'])->getJson('/api/v1/auth/me')->assertOk();

        // Old access + refresh are dead after rotation.
        $this->withToken($access1)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->withToken($refresh1)->postJson('/api/v1/auth/refresh')->assertUnauthorized();
    }

    public function test_access_token_cannot_be_used_to_refresh(): void
    {
        $user = $this->makeCitizen();
        ['access_token' => $access] = $this->loginTokens($user);

        // ability:refresh rejects the access token (ability 'access').
        $this->withToken($access)->postJson('/api/v1/auth/refresh')->assertForbidden();
    }

    public function test_refresh_token_cannot_call_normal_endpoints(): void
    {
        $user = $this->makeCitizen();
        ['refresh_token' => $refresh] = $this->loginTokens($user);

        // ability:access rejects the refresh token (ability 'refresh').
        $this->withToken($refresh)->getJson('/api/v1/auth/me')->assertForbidden();
    }

    /**
     * @return array{access_token:string,refresh_token:string}
     */
    private function loginTokens(User $user): array
    {
        return $this->postJson('/api/v1/auth/login', [
            'nin_or_email' => $user->nin,
            'password' => 'StrongP@ss123',
        ])->json('data.tokens');
    }
}
