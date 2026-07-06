<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\AlgerianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mirrors the web CitizenController@updateProfile rules: the same strict identity
 * validation so profile edits can't bypass registration/KYC checks.
 */
class UpdateProfileRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'phone' => ['sometimes', 'string', new AlgerianPhone, 'unique:users,phone,'.$userId],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$userId],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'commune_id' => ['sometimes', 'nullable', 'exists:communes,id'],
            'postal_code' => ['sometimes', 'nullable', 'regex:/^\d{5}$/'],
            'profession' => ['sometimes', 'nullable', 'string', 'max:100'],
            'locale' => ['sometimes', 'string', 'in:ar,fr,en'],
            'secret_question' => ['sometimes', 'nullable', Rule::in(array_keys((array) __('auth.secret_questions')))],
            'secret_answer' => [
                Rule::requiredIf(fn () => $this->filled('secret_question') && ! $this->user()?->secret_answer),
                'nullable', 'string', 'min:2', 'max:200',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'secret_answer.required' => __('profile.secret_answer_required'),
        ];
    }
}
