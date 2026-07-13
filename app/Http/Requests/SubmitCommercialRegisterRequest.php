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
            // The register's issue (start) date — a real, already-issued register,
            // so it cannot be in the future.
            'start_date' => ['required', 'date', 'before_or_equal:today'],

            // PDF or image scans, stored on the private disk (Law 18-07).
            'register_document' => [$registerDocRule, 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxKb],
            'tax_card_document' => [$taxCardRule, 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxKb],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.before_or_equal' => __('commercial-register.start_must_not_be_future'),
        ];
    }
}
