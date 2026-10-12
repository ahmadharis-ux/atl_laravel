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
    'user_id', 'product_id', 'order_number', 'target', 'target_zone', 'amount', 'status',
    'payment_method', 'payment_channel', 'payment_code', 'paid_at', 'notes',
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

    public function logs(): HasMany
    {
        return $this->hasMany(TransactionLog::class)->latest('id');
    }

    /**
     * Pemenuhan pesanan ini dikirim ke provider eksternal (mis. Digiflazz).
     */
    public function usesProviderFulfillment(): bool
    {
        return $this->provider !== null && $this->provider !== '';
    }

    /**
     * Nomor tujuan yang dikirim ke Digiflazz. Untuk produk yang butuh server
     * /zona (mis. game), Digiflazz memakai satu kolom `customer_no` dengan
     * zona ditempelkan di belakang nomor tujuan tanpa pemisah.
     */
    public function providerCustomerNo(): string
    {
        return $this->target.($this->target_zone !== null ? $this->target_zone : '');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'paid_at' => 'datetime',
            'provider_price' => 'integer',
            'provider_admin' => 'integer',
            'provider_payload' => 'array',
            'processed_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }
}
