<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Pembuat & hasher API key reseller.
 *
 * Key asli ("ap_XXXX...") hanya dikembalikan sekali saat dibuat; yang
 * disimpan di kolom `users.reseller_api_key` adalah hash SHA-256-nya agar
 * bocor di database pun key tidak bisa dipakai. Autentikasi API membandingkan
 * hash dari key yang masuk dengan kolom tersebut (lihat AuthenticateReseller).
 */
final class ResellerKey
{
    public const PREFIX = 'ap_';

    /**
     * Buat key baru.
     *
     * @return array{plain: string, hash: string, hint: string}
     */
    public static function generate(): array
    {
        $length = max(16, (int) config('reseller.key_random_length', 40));
        $plain = self::PREFIX.Str::upper(Str::random($length));

        return [
            'plain' => $plain,
            'hash' => self::hash($plain),
            'hint' => mb_substr($plain, 0, 11).'…',
        ];
    }

    /**
     * Hash SHA-256 dari key mentah (hex 64 karakter).
     */
    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
