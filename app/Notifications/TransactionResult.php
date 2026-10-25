<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi dalam aplikasi (database) untuk hasil transaksi user.
 *
 * Dipakai untuk top up saldo dan pembelian produk: berhasil maupun gagal.
 * Tidak dikirim lewat email/SMS — hanya muncul di pusat notifikasi (ikon lonceng).
 */
class TransactionResult extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $title,
        public string $body,
        public string $level = 'info',
        public ?string $icon = null,
        public ?string $url = null,
        public array $context = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'level' => $this->level,
            'icon' => $this->icon ?? $this->defaultIcon(),
            'url' => $this->url,
            'context' => $this->context,
        ];
    }

    private function defaultIcon(): string
    {
        return match ($this->level) {
            'success' => 'check_circle',
            'error' => 'error',
            'warning' => 'warning',
            default => 'notifications',
        };
    }
}
