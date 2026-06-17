<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The authenticated user's own profile. An explicit allowlist of fields — never
 * exposes password, secret answer or 2FA secrets (the model also hides them).
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nin_masked' => mask_nin($this->nin),
            'name' => $this->name,
            'first_name_ar' => $this->first_name_ar,
            'last_name_ar' => $this->last_name_ar,
            'first_name_fr' => $this->first_name_fr,
            'last_name_fr' => $this->last_name_fr,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'commune_id' => $this->commune_id,
            'postal_code' => $this->postal_code,
            'profession' => $this->profession,
            'locale' => $this->locale,
            'role' => $this->role?->value,
            'account_status' => $this->account_status?->value,
            'kyc_status' => $this->kyc_status?->value,
            'email_verified' => (bool) $this->email_verified,
            'phone_verified' => (bool) $this->phone_verified,
            'secret_question' => $this->secret_question,
            'has_secret_question' => filled($this->secret_question),
            // Computed capability flags the app uses to gate UI.
            'is_kyc_complete' => $this->isKycComplete(),
            'can_bid' => $this->canBid(),
            'is_premium' => $this->isPremium(),
            'is_blacklisted' => $this->isBlacklisted(),
        ];
    }
}
