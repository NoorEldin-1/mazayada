<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Services\EmailRecoveryService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Lost my email" recovery submission (multipart). The rules are shared with the
 * web form through EmailRecoveryService.
 */
class SubmitEmailRecoveryRequest extends FormRequest
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
        return EmailRecoveryService::rules();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return EmailRecoveryService::messages();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return EmailRecoveryService::attributes();
    }
}
