<?php

namespace App\Notifications\Channels;

use App\Models\UserDevice;
use App\Services\Push\PushMessage;
use App\Services\Push\PushSender;
use Illuminate\Notifications\Notification;

/**
 * Custom channel that delivers a notification to every device the notifiable has
 * registered (POST /api/v1/devices).
 *
 * A Notification opts in by returning this class from via() and implementing
 * toPush(): PushMessage — or nothing at all, in which case toInApp()'s
 * title/body/action_url are reused, so the in-app row and the push always say the
 * same thing.
 *
 * Tokens the provider reports as dead are deleted here, keeping the registry from
 * accumulating uninstalled apps.
 */
class PushChannel
{
    public function __construct(private readonly PushSender $sender) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $message = $this->message($notifiable, $notification);

        if ($message === null) {
            return;
        }

        $devices = UserDevice::where('user_id', $notifiable->getKey())->get();

        if ($devices->isEmpty()) {
            return;
        }

        $stale = $this->sender->send($devices->pluck('token')->all(), $message);

        if ($stale !== []) {
            UserDevice::whereIn('token', $stale)->delete();
        }
    }

    /** Build the payload, preferring an explicit toPush() over the in-app copy. */
    private function message(object $notifiable, Notification $notification): ?PushMessage
    {
        if (method_exists($notification, 'toPush')) {
            return $notification->toPush($notifiable);
        }

        if (! method_exists($notification, 'toInApp')) {
            return null;
        }

        $data = $notification->toInApp($notifiable);

        if (empty($data['title'])) {
            return null;
        }

        return new PushMessage(
            title: $data['title'],
            body: $data['body'] ?? '',
            data: [
                // Same vocabulary as NotificationResource.type — the app routes on this.
                'type' => $data['event'] ?? '',
                'action_url' => $data['action_url'] ?? '',
            ],
        );
    }
}
