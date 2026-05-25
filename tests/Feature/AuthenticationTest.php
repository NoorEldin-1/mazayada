<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_login_page_renders_for_guests(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_user_can_login_with_valid_nin_and_password(): void
    {
        $nin = $this->makeValidNin('109823041175663812');
        $user = $this->createUser(['nin' => $nin, 'password' => 'StrongP@ss123']);

        $response = $this->post('/login', [
            'nin_or_email' => $user->nin,
            'password' => 'StrongP@ss123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_user_can_login_with_email_and_password(): void
    {
        $user = $this->createUser(['email' => 'jane@example.test', 'password' => 'StrongP@ss123']);

        $this->post('/login', [
            'nin_or_email' => 'jane@example.test',
            'password' => 'StrongP@ss123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_login_fails_with_wrong_password_and_increments_failed_attempts(): void
    {
        $user = $this->createUser(['email' => 'fail@example.test', 'password' => 'StrongP@ss123']);

        $this->post('/login', [
            'nin_or_email' => 'fail@example.test',
            'password' => 'WrongPassword',
        ])->assertSessionHasErrors('nin_or_email');

        $this->assertSame(1, $user->fresh()->failed_login_attempts);
        $this->assertGuest();
    }

    public function test_account_is_locked_after_max_failed_attempts(): void
    {
        $user = $this->createUser(['email' => 'lock@example.test', 'password' => 'StrongP@ss123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'nin_or_email' => 'lock@example.test',
                'password' => 'Wrong'.$i,
            ]);
        }

        $user->refresh();
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->isLocked());
    }

    public function test_blacklisted_user_cannot_login(): void
    {
        $user = $this->createUser([
            'email' => 'blocked@example.test',
            'password' => 'StrongP@ss123',
            'is_blacklisted' => true,
            'blacklist_reason' => 'fraud',
        ]);

        $this->post('/login', [
            'nin_or_email' => 'blocked@example.test',
            'password' => 'StrongP@ss123',
        ])->assertSessionHasErrors('nin_or_email');

        $this->assertGuest();
    }

    public function test_logout_invalidates_session(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    /**
     * Helper: create a user with reasonable defaults.
     */
    private function createUser(array $overrides = []): User
    {
        $defaults = [
            'nin' => $this->makeValidNin('109823041175663812'),
            'first_name_ar' => 'تجربة',
            'last_name_ar' => 'مستخدم',
            'phone' => '0555'.random_int(100000, 999999),
            'email' => 'user'.uniqid().'@example.test',
            'birth_date' => '1990-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::CITIZEN,
            'kyc_status' => KycStatus::COMPLETE,
            'kyc_completed_at' => now(),
            'account_status' => AccountStatus::ACTIVE,
            'phone_verified' => true,
            'email_verified' => true,
        ];

        $user = User::create(array_merge($defaults, $overrides));
        $user->assignRole($user->role->value);

        return $user;
    }

    private function makeValidNin(string $base16): string
    {
        $base = substr($base16, 0, 16);
        $weights = [2, 3, 4, 5, 6, 7];
        $digits = str_split($base);
        $sum = 0;
        for ($i = 15; $i >= 0; $i--) {
            $sum += ((int) $digits[$i]) * $weights[(15 - $i) % 6];
        }

        return $base.str_pad((string) (97 - ($sum % 97)), 2, '0', STR_PAD_LEFT);
    }
}
