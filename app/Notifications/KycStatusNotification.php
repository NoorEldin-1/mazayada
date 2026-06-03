<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails the citizen when an admin decides their KYC submission, or when the
 * account is auto-suspended for an unfinished KYC (spec §10.1).
 *
 * Email-only, rendered in the recipient's preferred language (User implements
 * HasLocalePreference). `$type` selects the copy group: approved | rejected |
 * suspended. `$reason` carries the rejection reason for the "rejected" variant.
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
        return ['mail'];
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
