<?php

namespace App\Services;

use App\Models\Category;
use App\Models\NotificationPreference;
use App\Models\User;

/**
 * Reads and saves a citizen's notification preferences (client edits 27 · 30).
 *
 * Saving is a FULL replace, and the server — not the client draft — is the
 * source of truth: values the user may not hold are corrected before storing
 * (email for a non-subscriber while email is Premium-only; SMS while the
 * platform has no SMS provider). The saved state is returned for display.
 */
class NotificationPreferenceService
{
    public function emailRequiresPremium(): bool
    {
        return (bool) setting('subscriptions.email_requires_premium', true);
    }

    public function smsAvailable(): bool
    {
        return (bool) setting('notifications.sms_enabled', false);
    }

    public function emailAllowedFor(User $user): bool
    {
        return ! $this->emailRequiresPremium() || $user->isPremium();
    }

    public function for(User $user): NotificationPreference
    {
        return NotificationPreference::find($user->id)
            ?? new NotificationPreference(['user_id' => $user->id]);
    }

    /**
     * @param  array{channels: array{push?: bool, email?: bool, sms?: bool}, auction_categories?: array<int, int|string>, new_auction_alerts?: bool}  $data
     */
    public function update(User $user, array $data): NotificationPreference
    {
        $channels = (array) ($data['channels'] ?? []);

        $categoryIds = array_values(array_unique(array_map('intval', (array) ($data['auction_categories'] ?? []))));
        // Silently drop ids that are not (active) categories.
        $categoryIds = $categoryIds === [] ? [] : Category::whereIn('id', $categoryIds)->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        $pref = $this->for($user);
        $pref->fill([
            'push_enabled' => (bool) ($channels['push'] ?? false),
            'email_enabled' => (bool) ($channels['email'] ?? false) && $this->emailAllowedFor($user),
            'sms_enabled' => (bool) ($channels['sms'] ?? false) && $this->smsAvailable(),
            'new_auction_alerts' => (bool) ($data['new_auction_alerts'] ?? false),
            'auction_categories' => $categoryIds,
        ]);
        $pref->save();

        return $pref;
    }

    /**
     * The GET/PUT response body — one call loads the whole screen.
     *
     * @return array<string, mixed>
     */
    public function payload(User $user, ?NotificationPreference $pref = null): array
    {
        $pref ??= $this->for($user);

        return [
            'channels' => [
                'push' => (bool) $pref->push_enabled,
                'email' => (bool) $pref->email_enabled,
                'sms' => (bool) $pref->sms_enabled,
            ],
            'auction_categories' => $pref->categoryIds(),
            'new_auction_alerts' => (bool) $pref->new_auction_alerts,
            'available_categories' => Category::where('is_active', true)->orderBy('id')->get()
                ->map(fn (Category $c) => ['id' => $c->id, 'name' => $c->name])->values()->all(),
            'email_requires_premium' => $this->emailRequiresPremium(),
            'sms_available' => $this->smsAvailable(),
            'is_premium' => $user->isPremium(),
        ];
    }
}
