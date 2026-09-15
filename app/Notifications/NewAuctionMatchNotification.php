<?php

namespace App\Notifications;

use App\Models\Auction;
use App\Notifications\Channels\InAppChannel;
use App\Notifications\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "A new auction in a category you follow" (client edits 26 · 28 · 29).
 *
 * Unlike the lifecycle notifications, delivery follows the citizen's own
 * preferences: the in-app row is always written, push only when enabled, email
 * only when enabled (and — per platform setting — only for Premium subscribers).
 * The resolved channel list is computed by NewAuctionAlertService.
 */
class NewAuctionMatchNotification extends Notification
{
    use Queueable;

    public const EVENT = 'new_auction_match';

    /**
     * @param  array<int, string>  $channels  subset of ['mail', 'push']
     */
    public function __construct(
        public Auction $auction,
        public array $channels = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $via = [InAppChannel::class];

        if (in_array('push', $this->channels, true)) {
            $via[] = PushChannel::class;
        }
        if (in_array('mail', $this->channels, true)) {
            $via[] = 'mail';
        }

        return $via;
    }

    /** @return array<string, string> */
    private function params(): array
    {
        $this->auction->loadMissing('category');

        return [
            'auction' => $this->auction->localizedTitle(),
            'category' => $this->auction->category?->name ?? '',
            'price' => dzd_text((int) $this->auction->opening_price),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $params = $this->params();

        return (new MailMessage)
            ->subject(__('mail.events.'.self::EVENT.'.subject', $params))
            ->view('emails.auction-event', [
                'event' => self::EVENT,
                'params' => $params,
                'actionUrl' => route('auctions.show', $this->auction),
                'notifiable' => $notifiable,
            ]);
    }

    /**
     * @return array{title: string, body: string, event: string, action_url: string}
     */
    public function toInApp(object $notifiable): array
    {
        $params = $this->params();

        return [
            'title' => __('notifications.events.'.self::EVENT.'.title', $params),
            'body' => __('notifications.events.'.self::EVENT.'.body', $params),
            'event' => self::EVENT,
            'action_url' => route('auctions.show', $this->auction),
        ];
    }
}
