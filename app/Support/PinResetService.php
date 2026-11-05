<?php

namespace App\Support;

use App\Models\PinResetOtp;
use App\Models\PinResetToken;
use App\Models\User;
use App\Notifications\PinResetOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PinResetService
{
    public function sendOtp(User $user, ?Request $request = null): void
    {
        $resendSeconds = max(0, (int) config('pin.otp_resend_seconds', 60));
        $latest = PinResetOtp::query()
            ->where('user_id', $user->getKey())
            ->latest('id')
            ->first();

        if ($latest !== null && $latest->created_at->greaterThan(now()->subSeconds($resendSeconds))) {
            $wait = max(1, (int) now()->diffInSeconds($latest->created_at->copy()->addSeconds($resendSeconds)));

            throw ValidationException::withMessages([
                'otp' => "Tunggu {$wait} detik sebelum meminta kode OTP baru.",
            ]);
        }

        $maxPerHour = max(1, (int) config('pin.otp_max_per_hour', 5));
        $recentCount = PinResetOtp::query()
            ->where('user_id', $user->getKey())
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentCount >= $maxPerHour) {
            throw ValidationException::withMessages([
                'otp' => 'Terlalu banyak permintaan kode OTP. Coba lagi nanti.',
            ]);
        }

        $code = (string) random_int(100_000, 999_999);
        $ttlMinutes = max(1, (int) config('pin.otp_ttl_minutes', 10));

        $otp = DB::transaction(function () use ($user, $code, $request, $ttlMinutes): PinResetOtp {
            PinResetOtp::query()
                ->where('user_id', $user->getKey())
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            return PinResetOtp::query()->create([
                'user_id' => $user->getKey(),
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes($ttlMinutes),
                'request_ip' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 500, '') : null,
            ]);
        });

        $user->notify(new PinResetOtpNotification($code, $ttlMinutes));

        PinAuditLogger::log($user, 'pin.reset_requested', $request, ['otp_id' => $otp->getKey()]);
    }

    public function verifyOtp(User $user, string $code, ?Request $request = null): string
    {
        $maxAttempts = max(1, (int) config('pin.otp_max_attempts', 5));
        $ttlMinutes = max(1, (int) config('pin.reset_token_ttl_minutes', 10));

        $outcome = DB::transaction(function () use ($user, $code, $maxAttempts, $ttlMinutes): array {
            $otp = PinResetOtp::query()
                ->where('user_id', $user->getKey())
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($otp === null) {
                return ['status' => 'missing'];
            }

            if ((int) $otp->attempts >= $maxAttempts) {
                $otp->forceFill(['consumed_at' => now()])->save();

                return ['status' => 'locked', 'otp_id' => $otp->getKey()];
            }

            if (! Hash::check($code, $otp->code_hash)) {
                $attempts = (int) $otp->attempts + 1;
                $locked = $attempts >= $maxAttempts;

                $otp->forceFill([
                    'attempts' => $attempts,
                    'consumed_at' => $locked ? now() : null,
                ])->save();

                return [
                    'status' => $locked ? 'locked' : 'invalid',
                    'remaining' => max(0, $maxAttempts - $attempts),
                    'otp_id' => $otp->getKey(),
                ];
            }

            $otp->forceFill(['consumed_at' => now()])->save();

            PinResetToken::query()
                ->where('user_id', $user->getKey())
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            $plainToken = Str::random(64);

            $token = PinResetToken::query()->create([
                'user_id' => $user->getKey(),
                'pin_reset_otp_id' => $otp->getKey(),
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]);

            return [
                'status' => 'valid',
                'plain' => $plainToken,
                'otp_id' => $otp->getKey(),
                'token_id' => $token->getKey(),
            ];
        });

        if ($outcome['status'] === 'missing') {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        if ($outcome['status'] === 'locked') {
            throw ValidationException::withMessages([
                'otp' => 'Terlalu banyak percobaan kode OTP. Minta kode OTP baru.',
            ]);
        }

        if ($outcome['status'] === 'invalid') {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak sesuai. Sisa percobaan: '.$outcome['remaining'].'.',
            ]);
        }

        PinAuditLogger::log($user, 'pin.reset_otp_verified', $request, [
            'otp_id' => $outcome['otp_id'],
            'token_id' => $outcome['token_id'],
        ]);

        return $outcome['plain'];
    }

    public function findValidToken(User $user, ?string $plainToken): ?PinResetToken
    {
        if ($plainToken === null || $plainToken === '') {
            return null;
        }

        return PinResetToken::query()
            ->where('user_id', $user->getKey())
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}
