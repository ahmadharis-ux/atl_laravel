<?php

namespace App\Support;

use App\Enums\TransactionLogLevel;
use App\Models\Order;
use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Pencatat riwayat transaksi ke tabel `transaction_logs` (append-only).
 *
 * Dipakai untuk merekam siklus hidup pesanan (dibuat, menunggu bayar, lunas)
 * sekaligus kegagalan beserta stack trace-nya agar bisa ditelusuri dari admin.
 */
final class TransactionLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function record(
        TransactionLogLevel $level,
        string $event,
        string $message,
        array $context = [],
        ?Throwable $exception = null,
        ?Order $order = null,
        ?User $user = null,
    ): TransactionLog {
        return TransactionLog::create([
            'order_id' => $order?->getKey(),
            'user_id' => $user?->getKey() ?? Auth::id(),
            'level' => $level,
            'event' => $event,
            'message' => $message,
            'context' => $context === [] ? null : $context,
            'stack_trace' => $exception?->getTraceAsString(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(
        string $event,
        string $message,
        array $context = [],
        ?Order $order = null,
        ?User $user = null,
    ): TransactionLog {
        return self::record(TransactionLogLevel::Info, $event, $message, $context, null, $order, $user);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(
        string $event,
        string $message,
        array $context = [],
        ?Order $order = null,
        ?User $user = null,
    ): TransactionLog {
        return self::record(TransactionLogLevel::Warning, $event, $message, $context, null, $order, $user);
    }

    /**
     * Merekam kegagalan beserta stack trace. Pesan & kelas exception otomatis
     * dimasukkan ke `context` agar mudah dibaca di halaman admin.
     *
     * @param  array<string, mixed>  $context
     */
    public static function error(
        string $event,
        string $message,
        Throwable $exception,
        array $context = [],
        ?Order $order = null,
        ?User $user = null,
    ): TransactionLog {
        $context += [
            'exception' => $exception::class,
            'error' => $exception->getMessage(),
        ];

        return self::record(TransactionLogLevel::Error, $event, $message, $context, $exception, $order, $user);
    }
}
