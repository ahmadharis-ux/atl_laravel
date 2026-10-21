<?php

namespace App\Models;

use App\Enums\PointTransactionType;
use App\Enums\UserTier;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Support\Roles;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function merchandiseRedemptions(): HasMany
    {
        return $this->hasMany(MerchandiseRedemption::class);
    }

    /**
     * Tingkatan loyalitas berdasarkan akumulasi poin reward.
     */
    public function tier(): UserTier
    {
        return UserTier::fromPoints((int) $this->points);
    }

    public function addPoint(): void
    {
        $this->changePoints(1, PointTransactionType::OrderReward, 'Bonus poin pembelian');
    }

    /**
     * Ubah saldo poin user dan catat ledger point_transactions (dipanggil
     * di dalam transaksi DB oleh pemanggil bila perlu atomik).
     */
    public function changePoints(int $delta, PointTransactionType $type, ?string $description = null, ?Model $reference = null): void
    {
        $after = (int) $this->points + $delta;

        $this->forceFill(['points' => $after])->save();

        $this->pointTransactions()->create([
            'type' => $type,
            'points_change' => $delta,
            'points_after' => $after,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'description' => $description,
        ]);
    }

    /** @return HasMany<PointTransaction, $this> */
    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    /**
     * Kirim email verifikasi dengan template bertema Atlantic Pedia.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Kirim email atur ulang kata sandi dengan template bertema Atlantic Pedia.
     *
     * @param  mixed  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification((string) $token));
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function paymentTokens(): HasMany
    {
        return $this->hasMany(PaymentToken::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
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
            'is_reseller' => 'boolean',
            'reseller_since' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
