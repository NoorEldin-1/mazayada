<?php

namespace App\Notifications;

use App\Notifications\Channels\InAppChannel;
use App\Notifications\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies the citizen when an admin decides their KYC submission, or when the
 * account is auto-suspended for an unfinished KYC (spec §10.1).
 *
 * Email + in-app row + push, rendered in the recipient's preferred language
 * (User implements HasLocalePreference). `$type` selects the copy group:
 * approved | rejected | suspended. `$reason` carries the rejection reason.
 *
 * The in-app row used to be written by hand at each call site; it lives here now
 * so all three channels share one source of copy and one `event` type.
 */
class KycStatusNotification extends Notification
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
            'title' => __("kyc.notif_{$this->type}_title"),
            'body' => __("kyc.notif_{$this->type}_body", ['reason' => $this->reason ?? '']),
            'event' => "kyc_{$this->type}",
            'action_url' => route('citizen.kyc'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $group = "kyc_{$this->type}"; // mail.kyc_approved | kyc_rejected | kyc_suspended

        return (new MailMessage)
            ->subject(__("mail.{$group}.subject"))
            ->view('emails.kyc-status', [
                'user' => $notifiable,
                'group' => $group,
                'reason' => $this->reason,
            ]);
    }
}
