<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_registration_emails_an_otp_and_leaves_user_unverified(): void
    {
        Notification::fake();
        $nin = $this->makeValidNin('109823041175663812');

        $this->post('/register', $this->registerPayload($nin))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('verify-otp'));

        $user = User::where('nin', $nin)->firstOrFail();
        $this->assertFalse($user->email_verified, 'User must stay unverified until the code is confirmed.');
        Notification::assertSentTo($user, OtpVerificationNotification::class);
    }

    public function test_user_can_verify_with_the_correct_code(): void
    {
        $nin = $this->makeValidNin('109823041175663812');
        $this->post('/register', $this->registerPayload($nin));

        $user = User::where('nin', $nin)->firstOrFail();
        $otp = Cache::get("otp_register_{$user->id}");
        $this->assertNotNull($otp, 'An OTP should be cached after registration.');

        $this->post('/verify-otp', ['user_id' => $user->id, 'otp' => $otp])
            ->assertRedirect(route('citizen.dashboard'));

        $this->assertTrue($user->refresh()->email_verified);
        $this->assertAuthenticatedAs($user);
    }

    public function test_verification_fails_with_a_wrong_code(): void
    {
        $nin = $this->makeValidNin('109823041175663812');
        $this->post('/register', $this->registerPayload($nin));

        $user = User::where('nin', $nin)->firstOrFail();
        $otp = Cache::get("otp_register_{$user->id}");
        $wrong = str_pad((string) ((((int) $otp) + 1) % 1000000), 6, '0', STR_PAD_LEFT);

        $this->post('/verify-otp', ['user_id' => $user->id, 'otp' => $wrong])
            ->assertSessionHasErrors('otp');

        $this->assertFalse($user->refresh()->email_verified);
        $this->assertGuest();
    }

    public function test_resend_issues_a_fresh_code_and_emails_it_again(): void
    {
        Notification::fake();
        $nin = $this->makeValidNin('109823041175663812');
        $this->post('/register', $this->registerPayload($nin)); // 1st email

        $user = User::where('nin', $nin)->firstOrFail();

        $this->post('/verify-otp', ['user_id' => $user->id, 'resend' => 1])
            ->assertSessionHas('status'); // 2nd email

        Notification::assertSentToTimes($user, OtpVerificationNotification::class, 2);
    }

    public function test_resend_is_blocked_during_the_cooldown(): void
    {
        Notification::fake();
        $nin = $this->makeValidNin('109823041175663812');
        $this->post('/register', $this->registerPayload($nin));

        $user = User::where('nin', $nin)->firstOrFail();

        $this->post('/verify-otp', ['user_id' => $user->id, 'resend' => 1]); // allowed
        $this->post('/verify-otp', ['user_id' => $user->id, 'resend' => 1])  // blocked
            ->assertSessionHasErrors('otp');

        // register + first resend only — the throttled one must not send.
        Notification::assertSentToTimes($user, OtpVerificationNotification::class, 2);
    }

    public function test_unverified_user_is_routed_through_otp_on_login(): void
    {
        Notification::fake();
        $user = $this->createUser(['email_verified' => false]);

        $this->post('/login', ['nin_or_email' => $user->nin, 'password' => 'StrongP@ss123'])
            ->assertRedirect(route('verify-otp'));

        $this->assertGuest();
        Notification::assertSentTo($user, OtpVerificationNotification::class);
    }

    // ===== Helpers =====

    private function registerPayload(string $nin): array
    {
        return [
            'nin' => $nin,
            'first_name_ar' => 'سعيد',
            'last_name_ar' => 'بن أحمد',
            'phone' => '0555'.random_int(100000, 999999),
            'email' => 'otp'.uniqid().'@example.test',
            'birth_date' => '1990-05-12',
            'password' => 'StrongP@ss123',
            'password_confirmation' => 'StrongP@ss123',
        ];
    }

    private function createUser(array $overrides = []): User
    {
        $defaults = [
            'nin' => $this->makeValidNin('109823041175663823'),
            'first_name_ar' => 'تجربة',
            'last_name_ar' => 'مستخدم',
            'phone' => '0555'.random_int(100000, 999999),
            'email' => 'login'.uniqid().'@example.test',
            'birth_date' => '1990-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::CITIZEN,
            'account_status' => AccountStatus::ACTIVE,
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
