<?php

namespace App\Models;

use App\Enums\MarkupType;
use App\Enums\ProductPaymentType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'external_id', 'code', 'product_ref', 'name', 'payment_type',
    'brand_id', 'category_id', 'type_id', 'seller_id', 'description',
    'cost_price', 'max_price', 'markup_type', 'markup_value',
    'stock', 'unlimited_stock', 'start_cut_off', 'end_cut_off',
    'faktur', 'multi', 'multi_counter',
    'seller_sku_code', 'seller_sku_id', 'seller_sku_id_int', 'seller_connection_type',
    'seller_fatur_pajak', 'seller_sku_desc', 'seller_details',
    'status', 'status_seller_sku', 'last_update', 'sort_order', 'change',
    'sold_count',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Kategori yang punya alur pemesanan sendiri (deteksi nomor HP lewat
     * halaman /pulsa) dan tidak ditampilkan di katalog produk umum.
     *
     * @var list<string>
     */
    public const OPERATOR_CATEGORY_SLUGS = ['pulsa', 'data'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /**
     * Nama route daftar produk sesuai jenis pembayaran.
     */
    public function listRoute(): string
    {
        return $this->payment_type === ProductPaymentType::Prabayar
            ? 'admin.products.prepay.index'
            : 'admin.products.postpaid.index';
    }

    /**
     * Harga jual dihitung dari harga modal + aturan markup (tidak disimpan).
     */
    protected function sellingPrice(): Attribute
    {
        return Attribute::get(function (): int {
            $markup = $this->markup_type ?? MarkupType::Fixed;

            return $markup->apply((int) $this->cost_price, (float) $this->markup_value);
        });
    }

    public function scopePaymentType(Builder $query, ProductPaymentType $type): void
    {
        $query->where('payment_type', $type);
    }

    /**
     * Produk katalog umum: mengecualikan pulsa & paket data yang dipesan
     * lewat alur khusus di halaman /pulsa.
     */
    public function scopeForCatalog(Builder $query): void
    {
        $query->whereNotIn('category_id', Category::query()
            ->whereIn('slug', self::OPERATOR_CATEGORY_SLUGS)
            ->select('id'));
    }

    /**
     * Ekspresi SQL harga jual (harga modal + markup) dengan satu placeholder
     * yang diisi nilai enum {@see MarkupType::Percent}.
     */
    public static function sellingPriceExpression(): string
    {
        return '(CASE WHEN markup_type = ? THEN cost_price + (cost_price * markup_value / 100) ELSE cost_price + markup_value END)';
    }

    /**
     * Saring berdasarkan harga jual, yaitu harga modal ditambah markup.
     * Batas yang null diabaikan, jadi bisa dipakai untuk min/maks saja.
     */
    public function scopePriceBetween(Builder $query, ?int $min, ?int $max): void
    {
        $price = self::sellingPriceExpression();

        $query->when($min !== null, fn (Builder $q) => $q->whereRaw($price.' >= ?', [MarkupType::Percent->value, $min]))
            ->when($max !== null, fn (Builder $q) => $q->whereRaw($price.' <= ?', [MarkupType::Percent->value, $max]));
    }

    /**
     * Urutkan berdasarkan harga jual; arah selain "desc" dianggap menaik.
     */
    public function scopeOrderByPrice(Builder $query, string $direction = 'asc'): void
    {
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        $query->orderByRaw(self::sellingPriceExpression().' '.$direction, [MarkupType::Percent->value]);
    }

    /**
     * Urutkan dari produk paling banyak terjual.
     */
    public function scopeBestSeller(Builder $query): void
    {
        $query->orderByDesc('sold_count')->orderBy('name');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_type' => ProductPaymentType::class,
            'markup_type' => MarkupType::class,
            'cost_price' => 'integer',
            'max_price' => 'integer',
            'markup_value' => 'decimal:2',
            'stock' => 'integer',
            'unlimited_stock' => 'boolean',
            'faktur' => 'boolean',
            'multi' => 'boolean',
            'multi_counter' => 'integer',
            'seller_sku_id_int' => 'integer',
            'seller_fatur_pajak' => 'boolean',
            'seller_details' => 'array',
            'status' => 'boolean',
            'status_seller_sku' => 'integer',
            'last_update' => 'datetime',
            'sort_order' => 'integer',
            'change' => 'boolean',
            'sold_count' => 'integer',
        ];
    }
}
