<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A single KYC document upload. The size cap depends on the document type
 * (identity scans ≤ 1MB, biometric photo ≤ 120KB — spec §3.2). JPG/PNG only;
 * the file is stored on the private disk by the controller.
 */
class KycUploadRequest extends FormRequest
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
        $maxKb = $this->route('type') === 'photo-biometric'
            ? (int) setting('kyc.biometric_max_kb', 120)
            : (int) setting('kyc.doc_max_kb', 1024);

        return [
            'file' => ['required', 'image', 'mimes:jpeg,png', 'max:'.$maxKb],
        ];
    }
}
