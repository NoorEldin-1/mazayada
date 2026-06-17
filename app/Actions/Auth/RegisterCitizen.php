<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Creates a CITIZEN account from validated registration input. Shared by the web
 * AuthController and the mobile API so both create users identically (same fields,
 * role assignment and locale handling). The password is hashed by the model's
 * `password => hashed` cast.
 */
class RegisterCitizen
{
    /**
     * @param  array<string, mixed>  $data  Validated: nin, first_name_ar, last_name_ar, phone, email, birth_date, password
     */
    public function create(array $data, ?string $locale = null): User
    {
        $user = User::create([
            'nin' => $data['nin'],
            'first_name_ar' => $data['first_name_ar'],
            'last_name_ar' => $data['last_name_ar'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'birth_date' => $data['birth_date'],
            'password' => $data['password'],
            'role' => UserRole::CITIZEN,
            'locale' => $locale ?: config('locales.default', 'ar'),
        ]);

        $user->assignRole(UserRole::CITIZEN->value);

        return $user;
    }
}
