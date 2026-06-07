<?php

namespace App\Notifications;

use App\Notifications\Channels\InAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One parameterized notification for every auction lifecycle event (spec §10.1),
 * following the same copy-group pattern as KycStatusNotification. Delivered via
 * email + the in-app channel (SMS/Push deferred).
 *
 * Copy lives in:
 *   mail.events.{event}.{subject,line,cta}
 *   notifications.events.{event}.{title,body}
 *
 * $params provides the :placeholders (e.g. :auction, :amount, :days).
 */
class AuctionEventNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, string|int>  $params
     */
    public function __construct(
        public string $event,
        public array $params = [],
        public ?string $actionUrl = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', InAppChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__("mail.events.{$this->event}.subject", $this->params))
            ->view('emails.auction-event', [
                'event' => $this->event,
                'params' => $this->params,
                'actionUrl' => $this->actionUrl,
                'notifiable' => $notifiable,
            ]);
    }

    /**
     * @return array{title: string, body: string, action_url: ?string}
     */
    public function toInApp(object $notifiable): array
    {
        return [
            'title' => __("notifications.events.{$this->event}.title", $this->params),
            'body' => __("notifications.events.{$this->event}.body", $this->params),
            'action_url' => $this->actionUrl,
        ];
    }
}
