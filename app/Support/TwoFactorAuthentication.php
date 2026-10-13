<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Implementasi TOTP (RFC 6238) tanpa dependensi eksternal, dipakai untuk
 * aktivasi 2FA berbasis aplikasi authenticator.
 */
class TwoFactorAuthentication
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD = 30;

    public const DIGITS = 6;

    /**
     * Membuat secret acak dalam format base32.
     */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    /**
     * Memverifikasi kode TOTP terhadap secret, dengan toleransi jendela waktu.
     */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! ctype_digit($code) || strlen($code) !== self::DIGITS) {
            return false;
        }

        $timestamp = time();

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals(self::codeAt($secret, $timestamp + ($offset * self::PERIOD)), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kode TOTP yang berlaku saat ini (untuk pengujian).
     */
    public static function currentCode(string $secret): string
    {
        return self::codeAt($secret, time());
    }

    /**
     * URI otpauth:// yang bisa dibaca aplikasi authenticator.
     */
    public static function otpauthUri(string $secret, string $label, string $issuer): string
    {
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return 'otpauth://totp/'.rawurlencode($label).'?'.$query;
    }

    /**
     * Membuat sekumpulan kode pemulihan sekali pakai.
     *
     * @return list<string>
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn (): string => Str::upper(Str::random(5).'-'.Str::random(5)))
            ->values()
            ->all();
    }

    private static function codeAt(string $secret, int $timestamp): string
    {
        $key = self::base32Decode($secret);
        $counter = pack('N*', 0, intdiv($timestamp, self::PERIOD));
        $hash = hash_hmac('sha1', $counter, $key, true);
        $offset = ord($hash[19]) & 0x0F;

        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $data): string
    {
        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($binary, 5) as $chunk) {
            $encoded .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded;
    }

    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        $binary = '';

        foreach (str_split($secret) as $char) {
            $index = strpos(self::ALPHABET, $char);

            if ($index === false) {
                continue;
            }

            $binary .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';
        foreach (str_split($binary, 8) as $chunk) {
            if (strlen($chunk) < 8) {
                continue;
            }

            $decoded .= chr(bindec($chunk));
        }

        return $decoded;
    }
}
