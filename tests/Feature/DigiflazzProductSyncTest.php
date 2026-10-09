<?php

namespace Tests\Feature;

use App\Enums\MarkupType;
use App\Enums\ProductPaymentType;
use App\Models\Product;
use App\Support\Digiflazz;
use App\Support\DigiflazzProductSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DigiflazzProductSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_products_and_derives_master_data(): void
    {
        $this->fakePriceList();

        $count = $this->sync()->sync(ProductPaymentType::Prabayar);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('products', [
            'code' => 'ML-86',
            'name' => 'ML 86 Diamond',
            'payment_type' => 'prabayar',
            'cost_price' => 20300,
            'stock' => 10,
            'unlimited_stock' => false,
            'multi' => true,
            'status' => true,
            'status_seller_sku' => 1,
            'start_cut_off' => null,
        ]);
        $this->assertDatabaseHas('brands', ['name' => 'Mobile Legends']);
        $this->assertDatabaseHas('categories', ['name' => 'Games']);
        $this->assertDatabaseHas('types', ['name' => 'Umum']);
        $this->assertDatabaseHas('sellers', ['name' => 'Seller A', 'connection_type' => 'api']);
    }

    public function test_resync_updates_price_without_overwriting_admin_markup(): void
    {
        $product = Product::factory()->prabayar()->create([
            'code' => 'ML-86',
            'markup_type' => MarkupType::Percent,
            'markup_value' => 10,
            'cost_price' => 100,
        ]);

        $this->fakePriceList();

        $this->sync()->sync(ProductPaymentType::Prabayar);

        $product->refresh();

        $this->assertSame(20300, $product->cost_price);
        $this->assertSame(MarkupType::Percent, $product->markup_type);
        $this->assertSame('10.00', $product->markup_value);
    }

    public function test_it_skips_items_without_a_sku_code(): void
    {
        Http::fake([
            'api.digiflazz.com/v1/price-list' => Http::response(['data' => [
                ['product_name' => 'Tanpa Kode', 'brand' => 'X'],
            ]]),
        ]);

        $this->assertSame(0, $this->sync()->sync(ProductPaymentType::Prabayar));
        $this->assertDatabaseCount('products', 0);
    }

    private function sync(): DigiflazzProductSync
    {
        return new DigiflazzProductSync(new Digiflazz('user', 'key'));
    }

    private function fakePriceList(): void
    {
        Http::fake([
            'api.digiflazz.com/v1/price-list' => Http::response(['data' => [
                [
                    'product_name' => 'ML 86 Diamond',
                    'category' => 'Games',
                    'brand' => 'Mobile Legends',
                    'type' => 'Umum',
                    'seller_name' => 'Seller A',
                    'price' => 20300,
                    'buyer_sku_code' => 'ML-86',
                    'buyer_product_status' => true,
                    'seller_product_status' => true,
                    'unlimited_stock' => false,
                    'stock' => 10,
                    'multi' => true,
                    'start_cut_off' => '0:0',
                    'end_cut_off' => '0:0',
                    'desc' => 'Masukkin User ID.',
                ],
            ]]),
        ]);
    }
}
