<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Step 1 of secret-question recovery — identify the account and surface its
 * stored question.
 */
class RevealSecretQuestionRequest extends FormRequest
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
