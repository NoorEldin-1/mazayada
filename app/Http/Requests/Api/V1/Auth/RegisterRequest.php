<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Rules\AlgerianPhone;
use App\Rules\NinValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mirrors the web AuthController@register rules exactly.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nin' => ['required', 'string', new NinValidation, 'unique:users,nin'],
            'first_name_ar' => ['required', 'string', 'max:100'],
            'last_name_ar' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', new AlgerianPhone, 'unique:users,phone'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'birth_date' => ['required', 'date', 'before:'.now()->subYears(18)->toDateString()],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:80'],
        ];
    }
}
