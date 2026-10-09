<?php

namespace App\Models;

use Database\Factories\SearchHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'query', 'hits', 'last_searched_at'])]
class SearchHistory extends Model
{
    /** @use HasFactory<SearchHistoryFactory> */
    use HasFactory;

    /**
     * Jumlah kata kunci riwayat yang disimpan tiap pengguna.
     */
    public const MAX_PER_USER = 10;

    /**
     * Panjang maksimal kata kunci yang dicatat.
     */
    public const MAX_QUERY_LENGTH = 60;

    /**
     * Kolom waktu pencarian terakhir dipakai sebagai pengganti updated_at.
     */
    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Riwayat terbaru milik satu pengguna, paling baru di depan.
     *
     * @return list<string>
     */
    public static function recentQueries(int $userId): array
    {
        return static::query()
            ->forUser($userId)
            ->limit(self::MAX_PER_USER)
            ->pluck('query')
            ->all();
    }

    /**
     * Kata kunci milik pengguna diurutkan dari yang paling sering dicari.
     *
     * @return list<string>
     */
    public static function topQueries(int $userId, int $limit = 3): array
    {
        return static::query()
            ->where('user_id', $userId)
            ->orderByDesc('hits')
            ->orderByDesc('last_searched_at')
            ->limit($limit)
            ->pluck('query')
            ->all();
    }

    /**
     * Catat satu pencarian milik pengguna (tambah hitungan lalu buang yang kebanyakan).
     */
    public static function remember(int $userId, string $query): void
    {
        $term = trim($query);

        if ($term === '' || mb_strlen($term) > self::MAX_QUERY_LENGTH) {
            return;
        }

        $entry = static::query()->firstOrNew(['user_id' => $userId, 'query' => $term]);
        $entry->hits = ($entry->exists ? $entry->hits : 0) + 1;
        $entry->last_searched_at = now();
        $entry->save();

        static::prune($userId);
    }

    /**
     * Hapus seluruh riwayat milik satu pengguna.
     */
    public static function clearFor(int $userId): void
    {
        static::query()->where('user_id', $userId)->delete();
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)
            ->orderByDesc('last_searched_at')
            ->orderByDesc('id');
    }

    /**
     * Batasi tiap pengguna pada MAX_PER_USER entri terbaru.
     */
    protected static function prune(int $userId): void
    {
        $keep = static::query()
            ->where('user_id', $userId)
            ->orderByDesc('last_searched_at')
            ->orderByDesc('id')
            ->limit(self::MAX_PER_USER)
            ->pluck('id');

        static::query()
            ->where('user_id', $userId)
            ->whereNotIn('id', $keep)
            ->delete();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hits' => 'integer',
            'last_searched_at' => 'datetime',
        ];
    }
}
