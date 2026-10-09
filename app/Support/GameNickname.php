<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Cek nickname akun game lewat API publik api.isan.eu.org (sumber Codashop).
 *
 * API ini gratis dan tanpa kunci. Dokumentasi:
 * https://github.com/ihsangan/valid
 */
final class GameNickname
{
    /**
     * Pemetaan slug brand produk ke kode game api.isan.eu.org.
     *
     * @var array<string, string>
     */
    private const GAMES = [
        'free-fire' => 'ff',
        'mobile-legends' => 'ml',
        'arena-of-valor' => 'aov',
        'call-of-duty-mobile' => 'codm',
        'valorant' => 'valo',
        'point-blank' => 'pb',
    ];

    public function __construct(
        private readonly string $baseUrl = 'https://api.isan.eu.org/nickname',
    ) {}

    public static function make(): self
    {
        return new self(
            (string) config('services.game_nickname.base_url', 'https://api.isan.eu.org/nickname'),
        );
    }

    /**
     * Apakah brand mendukung pengecekan nickname.
     */
    public static function supports(string $brand): bool
    {
        return array_key_exists($brand, self::GAMES);
    }

    /**
     * Apakah brand memerlukan Zone ID (dikirim sebagai parameter server).
     */
    public static function requiresZone(string $brand): bool
    {
        return $brand === 'mobile-legends';
    }

    /**
     * Cek nickname akun. Mengembalikan status + nickname atau pesan gagal.
     *
     * @return array{status: bool, nickname?: string, message?: string}
     */
    public function check(string $brand, string $playerId, ?string $zone = null): array
    {
        $game = self::GAMES[$brand] ?? null;

        if ($game === null) {
            return ['status' => false, 'message' => 'Produk ini tidak memiliki validasi.'];
        }

        if (self::requiresZone($brand) && ($zone === null || $zone === '')) {
            return ['status' => false, 'message' => 'Zone ID wajib diisi.'];
        }

        $query = ['id' => $playerId];

        if ($zone !== null && $zone !== '') {
            $query['server'] = $zone;
        }

        $data = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->get($game, $query)
            ->json();

        if (! is_array($data)) {
            return ['status' => false, 'message' => 'Validasi sedang tidak tersedia, silakan coba lagi nanti.'];
        }

        if (($data['success'] ?? false) && ! empty($data['name'])) {
            return ['status' => true, 'nickname' => (string) $data['name']];
        }

        return [
            'status' => false,
            'message' => (string) ($data['message'] ?? 'Nama akun tidak ditemukan.'),
        ];
    }
}
