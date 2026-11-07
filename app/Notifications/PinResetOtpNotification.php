<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PinResetOtpNotification extends Notification
{
    public function __construct(
        private readonly string $code,
        private readonly int $ttlMinutes,
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
            ->subject('Kode OTP Reset PIN - Atlantic Pedia')
            ->view('emails.pin-reset-otp', [
                'name' => $notifiable->name,
                'code' => $this->code,
                'minutes' => $this->ttlMinutes,
            ]);
    }
}
