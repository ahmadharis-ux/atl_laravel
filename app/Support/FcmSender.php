<?php

namespace App\Support;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\WebPushConfig;
use Throwable;

/**
 * Mengirim push notification web (Firebase Cloud Messaging) ke semua perangkat
 * yang terdaftar milik seorang user.
 *
 * Bersifat opsional: jika FIREBASE_CREDENTIALS belum diisi, pengiriman dilewati
 * tanpa error sehingga notifikasi dalam aplikasi (database) tetap berjalan.
 */
final class FcmSender
{
    public function __construct(private ?Messaging $messaging = null) {}

    /**
     * @param  array{title?: string, body?: string, level?: string, icon?: string, url?: string}  $payload
     */
    public function sendToUser(User $user, array $payload): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $tokens = $user->deviceTokens()->pluck('token')->all();

        if ($tokens === []) {
            return;
        }

        try {
            $report = $this->messaging()->sendMulticast($this->buildMessage($payload), $tokens);
        } catch (Throwable $e) {
            Log::warning('Gagal mengirim push notification', [
                'user_id' => $user->getKey(),
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $stale = array_merge($report->invalidTokens(), $report->unknownTokens());

        if ($stale !== []) {
            DeviceToken::query()->whereIn('token', $stale)->delete();
        }
    }

    public function isConfigured(): bool
    {
        $value = (string) config('firebase.credentials');

        if ($value === '') {
            return false;
        }

        // JSON service account langsung di env.
        if (str_starts_with($value, '{') || str_starts_with($value, '[')) {
            return true;
        }

        // Path berkas service account (relatif ke root proyek bila bukan absolut).
        $path = str_starts_with($value, '/') ? $value : base_path($value);

        return is_file($path);
    }

    private function messaging(): Messaging
    {
        if ($this->messaging instanceof Messaging) {
            return $this->messaging;
        }

        $factory = (new Factory)->withServiceAccount($this->credentials());

        $projectId = config('firebase.project_id');

        if (filled($projectId)) {
            $factory = $factory->withProjectId((string) $projectId);
        }

        return $this->messaging = $factory->createMessaging();
    }

    /**
     * Kredensial service account: JSON langsung, atau path file (relatif ke root
     * proyek bila bukan path absolut).
     */
    private function credentials(): string
    {
        $value = (string) config('firebase.credentials');

        if ($value !== '' && ! str_starts_with($value, '{') && ! str_starts_with($value, '[') && ! str_starts_with($value, '/')) {
            return base_path($value);
        }

        return $value;
    }

    /**
     * @param  array{title?: string, body?: string, level?: string, icon?: string, url?: string}  $payload
     */
    private function buildMessage(array $payload): CloudMessage
    {
        $title = (string) ($payload['title'] ?? config('app.name'));
        $body = (string) ($payload['body'] ?? '');
        $level = (string) ($payload['level'] ?? 'info');
        $url = (string) ($payload['url'] ?? '');
        $icon = (string) ($payload['icon'] ?? '');

        $data = array_filter([
            'title' => $title,
            'body' => $body,
            'level' => $level,
            'url' => $url,
        ], static fn (string $value): bool => $value !== '');

        $notification = array_filter([
            'title' => $title,
            'body' => $body,
            'icon' => $icon,
            'data' => array_filter([
                'url' => $url,
                'level' => $level,
            ], static fn (string $value): bool => $value !== ''),
        ], static fn (string|array $value): bool => $value !== '' && $value !== []);

        $webPush = ['notification' => $notification];

        if ($url !== '') {
            $webPush['fcm_options'] = ['link' => $url];
        }

        return CloudMessage::new()
            ->withData($data)
            ->withNotification(Notification::create($title, $body))
            ->withWebPushConfig(WebPushConfig::fromArray($webPush));
    }
}
