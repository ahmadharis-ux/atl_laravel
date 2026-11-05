<?php

namespace App\Support;

use App\Enums\PinVerificationStatus;
use App\Models\PinResetToken;
use App\Models\User;
use App\Notifications\PinChangedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class PinService
{
    public function verify(User $user, ?string $pin, string $context, ?Request $request = null): PinVerificationResult
    {
        if ($pin === null || trim($pin) === '') {
            return PinVerificationResult::missing($user);
        }

        $pin = trim($pin);

        $result = DB::transaction(function () use ($user, $pin): PinVerificationResult {
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if (! filled($locked->pin_hash)) {
                return PinVerificationResult::missing($locked);
            }

            if ($locked->pin_locked_until !== null && $locked->pin_locked_until->isFuture()) {
                return PinVerificationResult::locked(
                    $locked,
                    max(1, (int) now()->diffInSeconds($locked->pin_locked_until)),
                );
            }

            if ($locked->pin_locked_until !== null) {
                $locked->forceFill([
                    'pin_attempts' => 0,
                    'pin_locked_until' => null,
                ])->save();
            }

            if (Hash::check($pin, $locked->pin_hash)) {
                if ((int) $locked->pin_attempts !== 0) {
                    $locked->forceFill(['pin_attempts' => 0])->save();
                }

                return PinVerificationResult::valid($locked);
            }

            $attempts = (int) $locked->pin_attempts + 1;
            $maxAttempts = max(1, (int) config('pin.max_attempts', 5));

            if ($attempts >= $maxAttempts) {
                $lockedUntil = now()->addMinutes(max(1, (int) config('pin.lockout_minutes', 15)));

                $locked->forceFill([
                    'pin_attempts' => $maxAttempts,
                    'pin_locked_until' => $lockedUntil,
                ])->save();

                return PinVerificationResult::locked(
                    $locked,
                    max(1, (int) now()->diffInSeconds($lockedUntil)),
                );
            }

            $locked->forceFill(['pin_attempts' => $attempts])->save();

            return PinVerificationResult::invalid($locked, $maxAttempts - $attempts);
        });

        $this->auditVerification($user, $result, $context, $request);

        return $result;
    }

    public function set(User $user, string $pin, ?Request $request = null): void
    {
        DB::transaction(function () use ($user, $pin): void {
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if (filled($locked->pin_hash)) {
                throw ValidationException::withMessages([
                    'pin' => 'PIN transaksi sudah diatur.',
                ]);
            }

            $locked->forceFill([
                'pin_hash' => Hash::make($pin),
                'pin_set_at' => now(),
                'pin_attempts' => 0,
                'pin_locked_until' => null,
            ])->save();
        });

        PinAuditLogger::log($user, 'pin.set', $request);
    }

    public function replace(
        User $user,
        string $pin,
        string $context,
        ?Request $request = null,
        ?PinResetToken $resetToken = null,
    ): void {
        DB::transaction(function () use ($user, $pin, $resetToken): void {
            if ($resetToken !== null) {
                $lockedToken = PinResetToken::query()
                    ->whereKey($resetToken->getKey())
                    ->where('user_id', $user->getKey())
                    ->whereNull('consumed_at')
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->first();

                if ($lockedToken === null) {
                    throw ValidationException::withMessages([
                        'token' => 'Sesi reset PIN tidak valid atau sudah kedaluwarsa. Silakan minta kode OTP baru.',
                    ]);
                }

                $lockedToken->forceFill(['consumed_at' => now()])->save();
            }

            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if (filled($locked->pin_hash) && Hash::check($pin, $locked->pin_hash)) {
                throw ValidationException::withMessages([
                    'pin' => 'PIN baru harus berbeda dari PIN lama.',
                ]);
            }

            $locked->forceFill([
                'pin_hash' => Hash::make($pin),
                'pin_set_at' => now(),
                'pin_reset_at' => now(),
                'pin_attempts' => 0,
                'pin_locked_until' => null,
            ])->save();
        });

        $this->invalidateOtherSessions($user, $request);

        PinAuditLogger::log($user, $context === 'reset' ? 'pin.reset' : 'pin.changed', $request);

        $user->notify(new PinChangedNotification);
    }

    public function highValueRetryAfterSeconds(User $user, int $amount): ?int
    {
        $threshold = (int) config('pin.high_value_threshold', 500_000);

        if ($amount <= $threshold || $user->pin_reset_at === null) {
            return null;
        }

        $until = $user->pin_reset_at->copy()->addHours(max(1, (int) config('pin.high_value_window_hours', 24)));

        if ($until->isPast()) {
            return null;
        }

        return max(1, (int) now()->diffInSeconds($until));
    }

    public function highValueMessage(int $retryAfterSeconds): string
    {
        $threshold = number_format((int) config('pin.high_value_threshold', 500_000), 0, ',', '.');
        $hours = max(1, (int) config('pin.high_value_window_hours', 24));
        $minutes = max(1, (int) ceil($retryAfterSeconds / 60));

        return "Transaksi di atas Rp {$threshold} dibatasi selama {$hours} jam setelah PIN direset. Coba lagi dalam {$minutes} menit.";
    }

    private function auditVerification(
        User $user,
        PinVerificationResult $result,
        string $context,
        ?Request $request,
    ): void {
        $metadata = ['context' => $context];

        if ($result->status === PinVerificationStatus::Invalid) {
            $metadata['remaining_attempts'] = $result->remainingAttempts;
        }

        if ($result->status === PinVerificationStatus::Locked) {
            $metadata['retry_after_seconds'] = $result->retryAfterSeconds;
        }

        if ($result->status === PinVerificationStatus::Missing) {
            return;
        }

        $action = match ($result->status) {
            PinVerificationStatus::Valid => 'pin.verified',
            PinVerificationStatus::Invalid => 'pin.verify_failed',
            PinVerificationStatus::Locked => 'pin.locked',
            default => 'pin.verified',
        };

        PinAuditLogger::log($user, $action, $request, $metadata);
    }

    private function invalidateOtherSessions(User $user, ?Request $request): void
    {
        if ($request === null || config('session.driver') !== 'database') {
            return;
        }

        DB::table('sessions')
            ->where('user_id', $user->getKey())
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
