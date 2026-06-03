<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end coverage for the locale foundation: default/RTL, guest switching,
 * persistence to the authenticated user, and carry-over into registration.
 */
class LanguageSwitchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_landing_defaults_to_arabic_rtl_for_guests(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('lang="ar"', false)
            ->assertSee('الرئيسية');
    }

    public function test_guest_can_switch_to_french(): void
    {
        $this->from('/')->get('/lang/fr')->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('lang="fr"', false)
            ->assertSee('Accueil');
    }

    public function test_guest_can_switch_to_english(): void
    {
        $this->from('/')->get('/lang/en')->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('Home');
    }

    public function test_unsupported_locale_is_ignored(): void
    {
        $this->from('/')->get('/lang/de')->assertRedirect('/');

        $this->get('/')->assertSee('dir="rtl"', false);
    }

    public function test_switching_persists_to_authenticated_user(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->from('/dashboard')->get('/lang/fr');

        $this->assertSame('fr', $user->fresh()->locale);
    }

    public function test_registration_carries_guest_locale_into_account(): void
    {
        $this->from('/')->get('/lang/fr');

        $nin = $this->makeValidNin('109823041175663823');

        $this->post('/register', [
            'nin' => $nin,
            'first_name_ar' => 'سعيد',
            'last_name_ar' => 'بن أحمد',
            'phone' => '0555000099',
            'email' => 'fr-user@example.test',
            'birth_date' => '1990-05-12',
            'password' => 'StrongP@ss123',
            'password_confirmation' => 'StrongP@ss123',
        ])->assertSessionHasNoErrors();

        $this->assertSame('fr', User::where('nin', $nin)->value('locale'));
    }

    public function test_admin_dashboard_has_switcher_and_respects_locale(): void
    {
        $admin = $this->makeAdmin();

        // Admin switches to French, then loads the admin dashboard.
        $this->actingAs($admin)->from('/admin')->get('/lang/fr');

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee(route('lang.switch', 'ar'), false)  // switcher link is present
            ->assertSee('Tableau de bord');                 // admin.nav_dashboard (fr)

        $this->assertSame('fr', $admin->fresh()->locale);
    }

    private function makeAdmin(): User
    {
        $admin = User::create([
            'nin' => '109823041175663801',
            'first_name_ar' => 'مشرف',
            'last_name_ar' => 'عام',
            'phone' => '0555'.random_int(100000, 999999),
            'email' => 'admin'.uniqid().'@example.test',
            'birth_date' => '1985-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::SUPER_ADMIN,
            'kyc_status' => KycStatus::COMPLETE,
            'account_status' => AccountStatus::ACTIVE,
            'phone_verified' => true,
            'email_verified' => true,
        ]);
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        return $admin;
    }

    private function makeUser(): User
    {
        $user = User::create([
            'nin' => '109823041175663812',
            'first_name_ar' => 'تجربة',
            'last_name_ar' => 'مستخدم',
            'phone' => '0555'.random_int(100000, 999999),
            'email' => 'lang'.uniqid().'@example.test',
            'birth_date' => '1990-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::CITIZEN,
            'kyc_status' => KycStatus::COMPLETE,
            'account_status' => AccountStatus::ACTIVE,
            'phone_verified' => true,
            'email_verified' => true,
        ]);
        $user->assignRole(UserRole::CITIZEN->value);

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
