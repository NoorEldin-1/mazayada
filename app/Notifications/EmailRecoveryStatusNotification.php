<?php

namespace App\Notifications;

use App\Notifications\Channels\InAppChannel;
use App\Notifications\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a citizen how their "lost my email" request was decided. `$type` selects
 * the copy group: approved | rejected.
 *
 * Channels depend on who receives it (see EmailRecoveryService::notify):
 *  - the User on approval  → mail (already the NEW address) + in-app + push;
 *  - the User on rejection → in-app + push only (their address is the lost one);
 *  - an on-demand route    → mail only, to the address the citizen requested.
 */
class EmailRecoveryStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,
        public ?string $reason = null,
        public string $newEmail = '',
        public string $name = '',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return $this->type === 'approved'
            ? ['mail', InAppChannel::class, PushChannel::class]
            : [InAppChannel::class, PushChannel::class];
    }

    /**
     * @return array{title: string, body: string, event: string, action_url: string}
     */
    public function toInApp(object $notifiable): array
    {
        return [
            'title' => __("email_recovery.notif.{$this->type}_title"),
            'body' => __("email_recovery.notif.{$this->type}_body", [
                'email' => mask_email($this->newEmail),
                'reason' => $this->reason ?? '',
            ]),
            'event' => "email_recovery_{$this->type}",
            'action_url' => route('login'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->name !== '' ? $this->name : (string) ($notifiable->name ?? '');

        return (new MailMessage)
            ->subject(__("email_recovery.mail.{$this->type}.subject"))
            ->view('emails.email-recovery-status', [
                'type' => $this->type,
                'name' => $name,
                'reason' => $this->reason,
                'newEmail' => $this->newEmail,
            ]);
    }
}
