<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Notification preferences
 *
 * Channels + preferred auction categories (edits 27 · 30). The PUT is a full
 * replace, and its response is the SAVED state — values the user may not hold
 * (e.g. email for a non-subscriber) come back corrected.
 */
class NotificationPreferenceController extends ApiController
{
    /**
     * Get preferences
     *
     * Current preferences plus every category to choose from, in one call.
     */
    public function show(Request $request, NotificationPreferenceService $preferences): JsonResponse
    {
        return $this->ok($preferences->payload($request->user()));
    }

    /**
     * Save preferences
     *
     * @bodyParam channels object required {push, email, sms} booleans.
     * @bodyParam auction_categories integer[] Category ids to follow; empty = all.
     * @bodyParam new_auction_alerts boolean required
     */
    public function update(Request $request, NotificationPreferenceService $preferences): JsonResponse
    {
        $validated = $request->validate([
            'channels' => ['required', 'array'],
            'channels.push' => ['required', 'boolean'],
            'channels.email' => ['required', 'boolean'],
            'channels.sms' => ['required', 'boolean'],
            'auction_categories' => ['present', 'array', 'max:100'],
            'auction_categories.*' => ['integer'],
            'new_auction_alerts' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $saved = $preferences->update($user, $validated);

        return $this->ok($preferences->payload($user, $saved), __('subscriptions.preferences_saved'));
    }
}
