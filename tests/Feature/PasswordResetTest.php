<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_step_one_emails_a_reset_code(): void
    {
        Notification::fake();
        $user = $this->createUser();

        $this->post('/reset-password', ['step' => 1, 'nin' => $user->nin, 'email' => $user->email])
            ->assertSessionHas('reset_step', 2)
            ->assertSessionHas('status');

        Notification::assertSentTo($user, OtpVerificationNotification::class);
    }

    public function test_user_can_reset_password_with_the_correct_code(): void
    {
        $user = $this->createUser();

        $this->post('/reset-password', ['step' => 1, 'nin' => $user->nin, 'email' => $user->email]);
        $otp = Cache::get("otp_reset_{$user->id}");
        $this->assertNotNull($otp, 'A reset OTP should be cached after step 1.');

        $this->post('/reset-password', [
            'step' => 2,
            'nin' => $user->nin,
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'NewStr0ng@Pass',
            'password_confirmation' => 'NewStr0ng@Pass',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewStr0ng@Pass', $user->refresh()->password));
    }

    public function test_reset_fails_with_a_wrong_code(): void
    {
        $user = $this->createUser();
        $originalHash = $user->password;

        $this->post('/reset-password', ['step' => 1, 'nin' => $user->nin, 'email' => $user->email]);

        $this->post('/reset-password', [
            'step' => 2,
            'nin' => $user->nin,
            'email' => $user->email,
            'otp' => '000000',
            'password' => 'NewStr0ng@Pass',
            'password_confirmation' => 'NewStr0ng@Pass',
        ])->assertSessionHasErrors('otp');

        $this->assertSame($originalHash, $user->refresh()->password, 'Password must not change on a wrong code.');
    }

    public function test_unknown_account_is_enumeration_safe(): void
    {
        Notification::fake();

        // A NIN/email pair that does not belong to any account.
        $this->post('/reset-password', [
            'step' => 1,
            'nin' => $this->makeValidNin('109823041175663898'),
            'email' => 'ghost@example.test',
        ])
            ->assertSessionHas('reset_step', 2)   // advances exactly like a real account
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();        // ...but no email is actually sent
    }

    public function test_resend_sends_a_fresh_code(): void
    {
        Notification::fake();
        $user = $this->createUser();

        $this->post('/reset-password', ['step' => 1, 'nin' => $user->nin, 'email' => $user->email]); // 1st
        $this->post('/reset-password', ['step' => 2, 'nin' => $user->nin, 'email' => $user->email, 'resend' => 1]) // 2nd
            ->assertSessionHas('status');

        Notification::assertSentToTimes($user, OtpVerificationNotification::class, 2);
    }

    // ===== Helpers =====

    private function createUser(array $overrides = []): User
    {
        $defaults = [
            'nin' => $this->makeValidNin('109823041175663812'),
            'first_name_ar' => 'تجربة',
            'last_name_ar' => 'مستخدم',
            'phone' => '0555'.random_int(100000, 999999),
            'email' => 'reset'.uniqid().'@example.test',
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
