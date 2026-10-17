<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\TransactionResult;
use App\Support\FcmSender;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Meneruskan notifikasi dalam aplikasi (TransactionResult) menjadi push
 * notification ke perangkat milik user lewat Firebase Cloud Messaging.
 *
 * Hanya menangani channel `database` agar tidak terkirim berkali-kali bila
 * kelak ada channel tambahan.
 */
class SendTransactionResultPush
{
    public function __construct(private readonly FcmSender $fcm) {}

    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        if (! $event->notifiable instanceof User) {
            return;
        }

        if (! $event->notification instanceof TransactionResult) {
            return;
        }

        $this->fcm->sendToUser(
            $event->notifiable,
            $event->notification->toArray($event->notifiable),
        );
    }
}
