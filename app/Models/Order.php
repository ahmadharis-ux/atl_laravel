<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'product_id', 'order_number', 'target', 'target_zone', 'amount', 'fee', 'status',
    'payment_method', 'payment_channel', 'payment_code', 'master_bank_id', 'paid_at', 'expires_at', 'metadata', 'notes',
    'provider', 'provider_ref', 'provider_sn', 'provider_status', 'provider_price',
    'provider_admin', 'provider_payload', 'processed_at', 'refunded_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function masterBank(): BelongsTo
    {
        return $this->belongsTo(MasterBank::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TransactionLog::class)->latest('id');
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    /**
     * Total yang harus ditransfer user: harga produk + biaya admin kanal
     * (VA/e-wallet manual). Untuk metode lain fee selalu 0.
     */
    public function totalDue(): int
    {
        return $this->amount + $this->fee;
    }

    /**
     * Pemenuhan pesanan ini dikirim ke provider eksternal (mis. Digiflazz).
     */
    public function usesProviderFulfillment(): bool
    {
        return $this->provider !== null && $this->provider !== ''
            || $this->product?->provider !== null && $this->product?->provider !== '';
    }

    /**
     * Nomor tujuan yang dikirim ke Digiflazz. Untuk produk yang butuh server
     * /zona (mis. game), Digiflazz memakai satu kolom `customer_no` dengan
     * zona ditempelkan di belakang nomor tujuan dipisah titik, mis. ID + Zone
     * ML dikirim sebagai `userId.zoneId`. Lihat `allow_dot` di Digiflazz::topup.
     */
    public function providerCustomerNo(): string
    {
        return $this->target.($this->target_zone !== null && $this->target_zone !== '' ? '.'.$this->target_zone : '');
    }

    /**
     * Apakah batas waktu pembayaran (khusus QRIS) sudah lewat.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'fee' => 'integer',
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
            'provider_price' => 'integer',
            'provider_admin' => 'integer',
            'provider_payload' => 'array',
            'processed_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }
}
