<?php

namespace App\Support;

use App\Enums\PinVerificationStatus;
use App\Models\User;

final readonly class PinVerificationResult
{
    public function __construct(
        public PinVerificationStatus $status,
        public ?int $userId = null,
        public ?int $remainingAttempts = null,
        public ?int $retryAfterSeconds = null,
    ) {}

    public static function valid(User $user): self
    {
        return new self(PinVerificationStatus::Valid, (int) $user->getKey());
    }

    public static function missing(?User $user = null): self
    {
        return new self(PinVerificationStatus::Missing, $user ? (int) $user->getKey() : null);
    }

    public static function invalid(User $user, int $remainingAttempts): self
    {
        return new self(PinVerificationStatus::Invalid, (int) $user->getKey(), $remainingAttempts);
    }

    public static function locked(User $user, int $retryAfterSeconds): self
    {
        return new self(PinVerificationStatus::Locked, (int) $user->getKey(), null, $retryAfterSeconds);
    }

    public function isValid(): bool
    {
        return $this->status === PinVerificationStatus::Valid;
    }

    public function isValidFor(User $user): bool
    {
        return $this->isValid() && $this->userId === (int) $user->getKey();
    }

    public function message(): string
    {
        return match ($this->status) {
            PinVerificationStatus::Valid => 'PIN sesuai.',
            PinVerificationStatus::Missing => 'PIN transaksi belum diatur. Atur PIN di Pengaturan > PIN.',
            PinVerificationStatus::Invalid => 'PIN tidak sesuai. Sisa percobaan: '.max(0, (int) $this->remainingAttempts).'.',
            PinVerificationStatus::Locked => 'PIN terkunci sementara. Coba lagi dalam '.$this->retryAfterMinutes().' menit.',
        };
    }

    public function retryAfterMinutes(): int
    {
        return max(1, (int) ceil(((int) $this->retryAfterSeconds) / 60));
    }
}
