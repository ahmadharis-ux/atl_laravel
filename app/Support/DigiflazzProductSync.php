<?php

namespace App\Support;

use App\Enums\ProductPaymentType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Type;
use Illuminate\Support\Str;

/**
 * Impor daftar harga Digiflazz ke katalog produk lokal.
 *
 * Master data (brand, kategori, tipe, seller) dibuat otomatis dari nama
 * yang dikirim Digiflazz. Produk di-upsert berdasarkan `code`
 * (buyer_sku_code) sehingga pengaturan markup milik admin tidak tertimpa.
 */
final class DigiflazzProductSync
{
    /**
     * Kolom yang ikut diperbarui saat produk sudah ada.
     *
     * Sengaja tidak menyertakan markup/max_price/sort_order/sold_count
     * agar nilai yang diatur admin tidak hilang saat sinkron berikutnya.
     *
     * @var array<int, string>
     */
    private const UPDATABLE = [
        'provider', 'name', 'region', 'payment_type',
        'brand_id', 'category_id', 'type_id', 'seller_id',
        'description', 'cost_price', 'stock', 'unlimited_stock', 'multi',
        'start_cut_off', 'end_cut_off',
        'seller_sku_code', 'seller_sku_desc',
        'status', 'status_seller_sku', 'last_update',
    ];

    /**
     * @var array<string, array<string, int>>
     */
    private array $masterIds = [];

    /**
     * @var array<string, int>
     */
    private array $sellerIds = [];

    public function __construct(private readonly Digiflazz $client) {}

    /**
     * Sinkronkan produk untuk satu jenis pembayaran dan kembalikan jumlahnya.
     */
    public function sync(ProductPaymentType $type = ProductPaymentType::Prabayar): int
    {
        $cmd = $type === ProductPaymentType::Prabayar ? 'prepaid' : 'pasca';

        $rows = collect($this->client->priceList($cmd))
            ->filter(fn (array $item): bool => filled($item['buyer_sku_code'] ?? null))
            ->keyBy('buyer_sku_code')
            ->map(fn (array $item): array => $this->toRow($item, $type))
            ->values()
            ->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            Product::upsert($chunk, uniqueBy: ['code'], update: self::UPDATABLE);
        }

        return count($rows);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function toRow(array $item, ProductPaymentType $type): array
    {
        return [
            'code' => (string) $item['buyer_sku_code'],
            'provider' => Product::PROVIDER_DIGIFLAZZ,
            'name' => (string) ($item['product_name'] ?? $item['buyer_sku_code']),
            'region' => ProductRegion::parse((string) ($item['product_name'] ?? '')),
            'payment_type' => $type->value,
            'brand_id' => $this->masterId(Brand::class, $item['brand'] ?? null),
            'category_id' => $this->masterId(Category::class, $item['category'] ?? null),
            'type_id' => $this->masterId(Type::class, $item['type'] ?? null),
            'seller_id' => $this->sellerId($item['seller_name'] ?? null),
            'description' => $item['desc'] ?? null,
            'cost_price' => max(0, (int) ($item['price'] ?? 0)),
            'stock' => max(0, (int) ($item['stock'] ?? 0)),
            'unlimited_stock' => (bool) ($item['unlimited_stock'] ?? false),
            'multi' => (bool) ($item['multi'] ?? false),
            'start_cut_off' => $this->time($item['start_cut_off'] ?? null),
            'end_cut_off' => $this->time($item['end_cut_off'] ?? null),
            'seller_sku_code' => (string) $item['buyer_sku_code'],
            'seller_sku_desc' => $item['desc'] ?? null,
            'status' => (bool) ($item['buyer_product_status'] ?? false),
            'status_seller_sku' => ($item['seller_product_status'] ?? false) ? 1 : 0,
            'last_update' => now(),
        ];
    }

    /**
     * @param  class-string<Brand|Category|Type>  $model
     */
    private function masterId(string $model, ?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return $this->masterIds[$model][$name] ??= $model::firstOrCreate(
            ['name' => $name],
            ['slug' => $this->uniqueSlug($model, $name)],
        )->getKey();
    }

    private function sellerId(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return $this->sellerIds[$name] ??= Seller::firstOrCreate(
            ['name' => $name],
            ['connection_type' => 'api'],
        )->getKey();
    }

    /**
     * @param  class-string<Brand|Category|Type>  $model
     */
    private function uniqueSlug(string $model, string $name): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $counter = 2;

        while ($model::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    /**
     * Ubah "23:45" menjadi "23:45:00"; anggap "0:0" (tanpa cut off) null.
     */
    private function time(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || preg_match('/^0{1,2}:0{1,2}(:0{1,2})?$/', $value)) {
            return null;
        }

        return substr_count($value, ':') === 1 ? $value.':00' : $value;
    }
}
