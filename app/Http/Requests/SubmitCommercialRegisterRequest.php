<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCommercialRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // A brand-new submission (no record yet) or a REJECTED one may be
        // (re)submitted; a PENDING-under-review or APPROVED record is locked.
        $register = $this->user()?->commercialRegister;

        return $this->user() !== null && ($register === null || $register->canSubmit());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) setting('commercial_register.doc_max_kb', 2048);

        // On resubmission the previously uploaded scans are kept unless replaced,
        // so a file is only *required* when none is on record yet.
        $register = $this->user()?->commercialRegister;
        $registerDocRule = $register?->register_document_path ? 'nullable' : 'required';
        $taxCardRule = $register?->tax_card_document_path ? 'nullable' : 'required';

        return [
            'company_name' => ['required', 'string', 'max:255'],
            'register_number' => ['required', 'string', 'max:100'],
            'tax_number' => ['required', 'string', 'max:100'],
            'activity_type' => ['required', 'string', 'max:255'],
            // Must be a currently-valid register, so its expiry is in the future.
            'expiry_date' => ['required', 'date', 'after:today'],

            // PDF or image scans, stored on the private disk (Law 18-07).
            'register_document' => [$registerDocRule, 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxKb],
            'tax_card_document' => [$taxCardRule, 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxKb],
        ];
    }

    public function messages(): array
    {
        return [
            'expiry_date.after' => __('commercial-register.expiry_must_be_future'),
        ];
    }
}
