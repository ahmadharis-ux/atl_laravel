<?php

namespace App\Models;

use App\Support\Roles;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'is_admin', 'points'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Roles::SUPERADMIN);
    }

    public function canAccessAdmin(): bool
    {
        return $this->can('dashboard.view');
    }

    public function primaryRole(): ?string
    {
        return $this->getRoleNames()->first();
    }

    public function roleLabel(): string
    {
        return $this->primaryRole() ? Roles::label($this->primaryRole()) : 'Tanpa peran';
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest('id');
    }

    public function points(): int
    {
        return (int) data_get($this, 'points', 0);
    }

    public function addPoint(): void
    {
        $this->forceFill(['points' => $this->points + 1])->save();
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function paymentTokens(): HasMany
    {
        return $this->hasMany(PaymentToken::class);
    }

    /**
     * Menandai email berubah: verifikasi lama tidak lagi berlaku.
     */
    public function resetEmailVerification(string $email): void
    {
        if ($this->email !== $email) {
            $this->forceFill([
                'email' => $email,
                'email_verified_at' => null,
            ])->save();
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'balance' => 'integer',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
