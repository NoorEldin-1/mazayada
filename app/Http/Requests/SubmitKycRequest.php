<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only a PENDING or REJECTED citizen may (re)submit — a submission under
        // review or an approved account is locked.
        return $this->user() !== null && $this->user()->kycCanSubmit();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name_fr' => ['required', 'string', 'max:100'],
            'last_name_fr' => ['required', 'string', 'max:100'],
            'father_name' => ['required', 'string', 'max:100'],
            'mother_fullname' => ['required', 'string', 'max:200'],
            'address' => ['required', 'string', 'max:255'],
            'wilaya_id' => ['required', 'integer', 'exists:wilayas,id'],
            // The commune must exist AND belong to the chosen wilaya — prevents
            // a tampered request from pairing a commune with the wrong province.
            'commune_id' => [
                'required',
                'integer',
                Rule::exists('communes', 'id')->where('wilaya_id', $this->input('wilaya_id')),
            ],
            'postal_code' => ['required', 'string', 'regex:/^\d{5}$/'],
            'profession' => ['nullable', 'string', 'max:100'],
            // Expected monthly income in DZD (spec §3.3) — stored as an integer.
            'expected_income' => ['nullable', 'integer', 'min:0'],
            'rip' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'commune_id.exists' => __('kyc.commune_wilaya_mismatch'),
            'postal_code.regex' => __('kyc.postal_code_invalid'),
        ];
    }
}
