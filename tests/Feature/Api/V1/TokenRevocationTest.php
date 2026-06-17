<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

class TokenRevocationTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_logout_revokes_the_current_device_pair(): void
    {
        $user = $this->makeCitizen();
        ['access_token' => $access, 'refresh_token' => $refresh] = $this->loginTokens($user);

        $this->withToken($access)->postJson('/api/v1/auth/logout')->assertOk();

        $this->withToken($access)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->withToken($refresh)->postJson('/api/v1/auth/refresh')->assertUnauthorized();
    }

    public function test_password_reset_revokes_all_tokens(): void
    {
        $user = $this->makeCitizen();
        ['access_token' => $access] = $this->loginTokens($user);

        $this->postJson('/api/v1/auth/password/request', [
            'nin' => $user->nin,
            'email' => $user->email,
        ])->assertOk();

        $otp = Cache::get("otp_reset_{$user->id}");

        $this->postJson('/api/v1/auth/password/verify', [
            'nin' => $user->nin,
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'NewStrongP@ss1',
            'password_confirmation' => 'NewStrongP@ss1',
        ])->assertOk();

        // Existing access token is dead after the reset.
        $this->withToken($access)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_blacklisted_account_token_is_rejected_on_next_request(): void
    {
        $user = $this->makeCitizen();
        ['access_token' => $access] = $this->loginTokens($user);

        // Token still valid before blacklist.
        $this->withToken($access)->getJson('/api/v1/auth/me')->assertOk();

        $user->update(['is_blacklisted' => true, 'blacklist_reason' => 'fraud']);

        // The runtime account-state guard rejects the still-present token.
        $this->withToken($access)->getJson('/api/v1/auth/me')->assertForbidden();
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
