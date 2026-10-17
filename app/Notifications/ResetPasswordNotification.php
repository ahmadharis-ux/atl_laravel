<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Email atur ulang kata sandi dengan template bertema Atlantic Pedia.
 */
class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Atur Ulang Kata Sandi - Atlantic Pedia')
            ->view('emails.reset-password', [
                'name' => $notifiable->name,
                'url' => $this->resetUrl($notifiable),
                'count' => $this->expireMinutes(),
            ]);
    }

    private function expireMinutes(): int
    {
        return (int) config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire',
            60,
        );
    }
}
