<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails a 6-digit one-time code used to verify the user's email address
 * during registration (and re-issued on "resend" / unverified login).
 *
 * Email-only by design — there is no SMS channel. The message is rendered
 * in the recipient's preferred language because User implements
 * HasLocalePreference, so Laravel sets the locale before building it.
 */
class OtpVerificationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $otp,
        public int $expiresInMinutes = 5,
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
        return (new MailMessage)
            ->subject(__('mail.otp.subject'))
            ->view('emails.otp', [
                'user' => $notifiable,
                'otp' => $this->otp,
                'expiresInMinutes' => $this->expiresInMinutes,
            ]);
    }
}
