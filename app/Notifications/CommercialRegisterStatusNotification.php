<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails the user when an admin decides their Commercial Register submission.
 *
 * Email-only, rendered in the recipient's preferred language (User implements
 * HasLocalePreference). `$type` selects the copy group: approved | rejected.
 * `$reason` carries the rejection reason for the "rejected" variant.
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
        return ['mail'];
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
