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
            // The KYC form asks for the wilaya before the commune; users only
            // store the commune, so derive it rather than making the client
            // reverse-look it up through /wilayas.
            'wilaya_id' => $this->commune?->wilaya_id,
            'postal_code' => $this->postal_code,
            'profession' => $this->profession,
            'locale' => $this->locale,

            // --- KYC form prefill -------------------------------------------
            // The full set of identity fields SubmitKycRequest accepts, so a
            // resubmission (e.g. after a rejection over one field) can be
            // pre-populated instead of retyped. Null until first submitted.
            'birth_date' => $this->birth_date?->toDateString(),
            'birth_place' => $this->birth_place,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'mother_surname' => $this->mother_surname,
            'expected_income' => $this->expected_income,
            'id_card_number' => $this->id_card_number,
            'passport_number' => $this->passport_number,
            'license_number' => $this->license_number,
            // Financial identifiers — the user's own, returned in full because
            // the form needs to show what is already on file.
            'rip' => $this->rip,
            'nif' => $this->nif,
            'nis' => $this->nis,
            'role' => $this->role?->value,
            'account_status' => $this->account_status?->value,
            'account_type' => $this->account_type?->value,
            'is_institution' => $this->isInstitution(),
            'entity' => $this->whenLoaded('entity', fn () => $this->entity ? [
                'id' => $this->entity->id,
                'name' => $this->entity->name,
            ] : null),
            'kyc_status' => $this->kyc_status?->value,
            // Commercial Register (السجل التجاري) summary — null status = never
            // submitted. has_commerce_register is the participation-gating flag.
            'commercial_register_status' => $this->commercialRegister?->status?->value,
            'has_commerce_register' => $this->hasCommerceRegister(),
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
