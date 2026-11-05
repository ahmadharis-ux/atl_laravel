<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PinChangedNotification extends Notification
{
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
            ->subject('PIN Transaksi Diubah - Atlantic Pedia')
            ->view('emails.pin-changed', [
                'name' => $notifiable->name,
                'changedAt' => now()->translatedFormat('d F Y H:i'),
            ]);
    }
}
