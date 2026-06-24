<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Eligibility (closed + within window + participant + valid bid) and the
     * one-per-auction rule are enforced by AppealService so the controller can
     * return a friendly message instead of a bare 403.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
