<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_user_can_register_with_valid_data(): void
    {
        $nin = $this->makeValidNin('109823041175663823');

        $response = $this->post('/register', [
            'nin' => $nin,
            'first_name_ar' => 'سعيد',
            'last_name_ar' => 'بن أحمد',
            'phone' => '0555000099',
            'email' => 'saeed@example.test',
            'birth_date' => '1990-05-12',
            'password' => 'StrongP@ss123',
            'password_confirmation' => 'StrongP@ss123',
        ]);

        // If validation fails we want a useful error in the test output.
        $response->assertSessionHasNoErrors();

        $user = User::where('nin', $nin)->first();
        $this->assertNotNull($user, 'User should be created on valid registration.');
        $this->assertTrue($user->hasRole(UserRole::CITIZEN->value));
    }

    public function test_register_rejects_invalid_nin(): void
    {
        $this->post('/register', [
            'nin' => '12345', // too short
            'first_name_ar' => 'سعيد',
            'last_name_ar' => 'بن أحمد',
            'phone' => '0555000099',
            'email' => 'saeed@example.test',
            'birth_date' => '1990-05-12',
            'password' => 'StrongP@ss123',
            'password_confirmation' => 'StrongP@ss123',
        ])->assertSessionHasErrors('nin');
    }

    public function test_register_rejects_invalid_phone(): void
    {
        $nin = $this->makeValidNin('109823041175663834');

        $this->post('/register', [
            'nin' => $nin,
            'first_name_ar' => 'سعيد',
            'last_name_ar' => 'بن أحمد',
            'phone' => '0455000099', // 04x is invalid (only 05/06/07)
            'email' => 'saeed2@example.test',
            'birth_date' => '1990-05-12',
            'password' => 'StrongP@ss123',
            'password_confirmation' => 'StrongP@ss123',
        ])->assertSessionHasErrors('phone');
    }

    public function test_register_rejects_user_under_18(): void
    {
        $nin = $this->makeValidNin('109823041175663845');

        $this->post('/register', [
            'nin' => $nin,
            'first_name_ar' => 'فتى',
            'last_name_ar' => 'صغير',
            'phone' => '0555000077',
            'email' => 'minor@example.test',
            'birth_date' => now()->subYears(17)->toDateString(),
            'password' => 'StrongP@ss123',
            'password_confirmation' => 'StrongP@ss123',
        ])->assertSessionHasErrors('birth_date');
    }

    public function test_register_requires_password_confirmation(): void
    {
        $nin = $this->makeValidNin('109823041175663856');

        $this->post('/register', [
            'nin' => $nin,
            'first_name_ar' => 'سعيد',
            'last_name_ar' => 'بن أحمد',
            'phone' => '0555000088',
            'email' => 'mismatch@example.test',
            'birth_date' => '1990-05-12',
            'password' => 'StrongP@ss123',
            'password_confirmation' => 'Different456',
        ])->assertSessionHasErrors('password');
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
