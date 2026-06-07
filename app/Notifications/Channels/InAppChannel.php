<?php

namespace App\Notifications\Channels;

use App\Enums\NotificationChannel;
use App\Models\UserNotification;
use Illuminate\Notifications\Notification;

/**
 * Custom Laravel notification channel that persists an in-app notification row
 * (the custom `notifications` table via UserNotification). A Notification opts
 * in by returning this class from via() and implementing toInApp(): array with
 * keys title, body, action_url.
 *
 * SMS/Push are intentionally NOT registered yet — the via()/toInApp() contract
 * plus the NotificationChannel enum keep them pluggable later (decision: email
 * + in-app only for now).
 */
class InAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toInApp')) {
            return;
        }

        $data = $notification->toInApp($notifiable);

        if (empty($data['title'])) {
            return;
        }

        UserNotification::record(
            userId: $notifiable->getKey(),
            title: $data['title'],
            body: $data['body'] ?? '',
            actionUrl: $data['action_url'] ?? null,
            channel: NotificationChannel::IN_APP->value,
        );
    }
}
