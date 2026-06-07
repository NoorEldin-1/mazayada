<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecretRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function makeUser(): User
    {
        return User::create([
            'nin' => '109823041175663801',
            'first_name_ar' => 'مستخدم',
            'last_name_ar' => 'تجربة',
            'phone' => '0555123456',
            'email' => 'recover@mazayada.test',
            'birth_date' => '1990-01-01',
            'password' => 'OldP@ss123',
            'role' => UserRole::CITIZEN,
            'secret_question' => 'mother_maiden',
            'secret_answer' => 'Benali', // hashed by the model cast
            'email_verified' => true,
        ]);
    }

    public function test_step_one_reveals_the_question_for_a_matching_account(): void
    {
        $user = $this->makeUser();

        $this->post('/recover', ['step' => 1, 'nin' => $user->nin, 'email' => $user->email])
            ->assertSessionHas('recover_step', 2)
            ->assertSessionHas('recover_question', 'mother_maiden');
    }

    public function test_unknown_account_gets_a_generic_error(): void
    {
        $this->post('/recover', ['step' => 1, 'nin' => '109823041175663999', 'email' => 'nobody@mazayada.test'])
            ->assertSessionHasErrors('nin');
    }

    public function test_correct_answer_resets_the_password(): void
    {
        $user = $this->makeUser();

        $this->post('/recover', [
            'step' => 2,
            'nin' => $user->nin,
            'email' => $user->email,
            'secret_answer' => 'Benali',
            'password' => 'NewP@ss123',
            'password_confirmation' => 'NewP@ss123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewP@ss123', $user->fresh()->password));
    }

    public function test_wrong_answer_does_not_reset(): void
    {
        $user = $this->makeUser();

        $this->post('/recover', [
            'step' => 2,
            'nin' => $user->nin,
            'email' => $user->email,
            'secret_answer' => 'WrongAnswer',
            'password' => 'NewP@ss123',
            'password_confirmation' => 'NewP@ss123',
        ])->assertSessionHasErrors('secret_answer');

        $this->assertTrue(Hash::check('OldP@ss123', $user->fresh()->password));
    }
}
