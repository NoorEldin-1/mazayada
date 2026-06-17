<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Profile
 *
 * View and update the authenticated citizen's editable profile fields.
 */
class ProfileController extends ApiController
{
    /**
     * Get profile
     */
    public function show(Request $request): JsonResponse
    {
        return $this->ok(new UserResource($request->user()));
    }

    /**
     * Update profile
     *
     * Updates contact details, address and the security question. Identity fields
     * (NIN, names) are not editable here.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $fields = $request->safe()->only([
            'phone', 'email', 'address', 'commune_id', 'postal_code', 'profession', 'secret_question',
        ]);

        // Only overwrite the stored answer when a new one was actually provided.
        if ($request->filled('secret_answer')) {
            $fields['secret_answer'] = $request->input('secret_answer');
        }

        $user->update($fields);

        return $this->ok(new UserResource($user->fresh()), __('profile.flash_updated'));
    }
}
