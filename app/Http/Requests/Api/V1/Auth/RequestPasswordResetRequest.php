<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Step 1 of password reset — identify the account (NIN + email) so an OTP can be
 * issued. The response is always neutral (enumeration-safe).
 */
class RequestPasswordResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nin' => ['required', 'string'],
            'email' => ['required', 'email'],
        ];
    }
}
