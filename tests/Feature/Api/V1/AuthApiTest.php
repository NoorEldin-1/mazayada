<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

class AuthApiTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_register_creates_unverified_user_and_sends_otp(): void
    {
        Notification::fake();

        $payload = $this->registerPayload();

        $response = $this->postJson('/api/v1/auth/register', $payload)
            ->assertCreated()
            ->assertJsonStructure(['data' => ['user_id'], 'message', 'meta']);

        $user = User::where('nin', $payload['nin'])->firstOrFail();
        $this->assertFalse((bool) $user->email_verified);
        $this->assertSame($user->id, $response->json('data.user_id'));
        Notification::assertSentTo($user, OtpVerificationNotification::class);
    }

    public function test_verify_otp_returns_an_access_and_refresh_pair(): void
    {
        $payload = $this->registerPayload();
        $this->postJson('/api/v1/auth/register', $payload)->assertCreated();

        $user = User::where('nin', $payload['nin'])->firstOrFail();
        $otp = Cache::get("otp_register_{$user->id}");

        $this->postJson('/api/v1/auth/verify-otp', ['user_id' => $user->id, 'otp' => $otp])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'can_bid', 'kyc_status'],
                    'tokens' => ['access_token', 'refresh_token', 'token_type', 'expires_in', 'refresh_expires_in'],
                ],
                'message',
            ]);

        $this->assertTrue((bool) $user->refresh()->email_verified);
    }

    public function test_login_returns_a_token_pair(): void
    {
        $user = $this->makeCitizen();

        $this->postJson('/api/v1/auth/login', [
            'nin_or_email' => $user->nin,
            'password' => 'StrongP@ss123',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['tokens' => ['access_token', 'refresh_token']]]);
    }

    public function test_login_with_wrong_password_returns_422(): void
    {
        $user = $this->makeCitizen();

        $this->postJson('/api/v1/auth/login', [
            'nin_or_email' => $user->nin,
            'password' => 'wrong-password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nin_or_email');
    }

    public function test_unverified_login_returns_needs_verification_flag(): void
    {
        Notification::fake();
        $user = $this->makeCitizen(['email_verified' => false]);

        $this->postJson('/api/v1/auth/login', [
            'nin_or_email' => $user->nin,
            'password' => 'StrongP@ss123',
        ])
            ->assertOk()
            ->assertJsonPath('data.needs_email_verification', true)
            ->assertJsonPath('data.user_id', $user->id);

        Notification::assertSentTo($user, OtpVerificationNotification::class);
    }

    public function test_blacklisted_user_cannot_login(): void
    {
        $user = $this->makeCitizen(['is_blacklisted' => true, 'blacklist_reason' => 'fraud']);

        $this->postJson('/api/v1/auth/login', [
            'nin_or_email' => $user->nin,
            'password' => 'StrongP@ss123',
        ])->assertStatus(422);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = $this->makeCitizen();
        $token = $this->accessTokenFor($user);

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.is_kyc_complete', true);
    }

    // ===== Helpers =====

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'nin' => str_pad((string) random_int(0, 999999999999999999), 18, '0', STR_PAD_LEFT),
            'first_name_ar' => 'سعيد',
            'last_name_ar' => 'بن أحمد',
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => 'api'.uniqid().'@example.test',
            'birth_date' => '1990-05-12',
            'password' => 'StrongP@ss123',
            'password_confirmation' => 'StrongP@ss123',
        ], $overrides);
    }

    private function accessTokenFor(User $user): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'nin_or_email' => $user->nin,
            'password' => 'StrongP@ss123',
        ])->json('data.tokens.access_token');
    }
}
