<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppealRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
            // Only an auction the user actually participated in may be referenced.
            'auction_id' => [
                'nullable',
                Rule::exists('auction_participants', 'auction_id')->where('user_id', $this->user()?->id),
            ],
        ];
    }
}
