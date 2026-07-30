<?php

namespace App\Notifications;

use App\Notifications\Channels\InAppChannel;
use App\Notifications\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies the user when an admin decides their Commercial Register submission.
 *
 * Email + in-app row + push, all rendered in the recipient's preferred language
 * (User implements HasLocalePreference, and the notification sender switches the
 * locale for the whole dispatch). `$type` selects the copy group:
 * approved | rejected. `$reason` carries the rejection reason.
 *
 * The in-app row used to be written by hand in AdminCommercialRegisterController;
 * it lives here now so email, in-app and push cannot drift apart and every
 * channel carries the same `event` type.
 */
class CommercialRegisterStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,
        public ?string $reason = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', InAppChannel::class, PushChannel::class];
    }

    /**
     * @return array{title: string, body: string, event: string, action_url: string}
     */
    public function toInApp(object $notifiable): array
    {
        return [
            'title' => __("commercial-register.notif_{$this->type}_title"),
            'body' => __("commercial-register.notif_{$this->type}_body", ['reason' => $this->reason ?? '']),
            'event' => "commercial_register_{$this->type}",
            'action_url' => route('citizen.commercial-register'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $group = "cr_{$this->type}"; // mail.cr_approved | mail.cr_rejected

        return (new MailMessage)
            ->subject(__("mail.{$group}.subject"))
            ->view('emails.commercial-register-status', [
                'user' => $notifiable,
                'group' => $group,
                'reason' => $this->reason,
            ]);
    }
}
