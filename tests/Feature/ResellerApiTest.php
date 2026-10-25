<?php

namespace Tests\Feature;

use App\Jobs\SendDigiflazzTransaction;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\ResellerKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ResellerApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: string} user & plain API key
     */
    private function reseller(array $attributes = []): array
    {
        $key = ResellerKey::generate();

        $user = User::factory()->create(array_merge([
            'balance' => 1_000_000,
            'is_reseller' => true,
            'reseller_api_key' => $key['hash'],
            'reseller_api_key_hint' => $key['hint'],
            'reseller_since' => now(),
        ], $attributes));

        return [$user, $key['plain']];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(array $attributes = []): Product
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'games'],
            Category::factory()->raw(['slug' => 'games']),
        );

        return Product::factory()->create(array_merge([
            'status' => true,
            'category_id' => $category->getKey(),
            'cost_price' => 10_000,
            'markup_value' => 1_000,
            'unlimited_stock' => false,
            'stock' => 5,
        ], $attributes));
    }

    public function test_request_without_an_api_key_is_unauthorized(): void
    {
        $this->getJson('/api/v1/reseller/profile')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'API key tidak ditemukan. Sertakan header X-API-Key: <api key> atau Authorization: Bearer <api key>.');
    }

    public function test_invalid_api_key_is_rejected(): void
    {
        $this->withHeaders(['X-API-Key' => 'ap_INVALIDKEY'])
            ->getJson('/api/v1/reseller/profile')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'API key tidak valid atau akun kamu bukan reseller aktif.');
    }

    public function test_non_reseller_users_key_is_rejected(): void
    {
        $key = ResellerKey::generate();
        User::factory()->create([
            'is_reseller' => false,
            'reseller_api_key' => $key['hash'],
            'reseller_api_key_hint' => $key['hint'],
        ]);

        $this->withHeaders(['X-API-Key' => $key['plain']])
            ->getJson('/api/v1/reseller/profile')
            ->assertUnauthorized();
    }

    public function test_deactivated_resellers_key_is_rejected(): void
    {
        [, $plain] = $this->reseller();
        User::query()->where('reseller_api_key', ResellerKey::hash($plain))
            ->update(['is_reseller' => false]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/profile')
            ->assertUnauthorized();
    }

    public function test_bearer_token_authentication_works(): void
    {
        [$user, $plain] = $this->reseller(['balance' => 55_000]);

        $this->withToken($plain)
            ->getJson('/api/v1/reseller/profile')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.balance', 55_000)
            ->assertJsonPath('data.discount_percent', 5);
    }

    public function test_balance_endpoint_returns_balance_and_transactions(): void
    {
        [, $plain] = $this->reseller(['balance' => 75_000]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/balance')
            ->assertOk()
            ->assertJsonPath('data.balance', 75_000)
            ->assertJsonPath('data.transactions', []);
    }

    public function test_products_index_returns_reseller_price_and_discount(): void
    {
        [, $plain] = $this->reseller();
        $this->product(['name' => 'Diamond 86', 'code' => 'ML86']);

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/products')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Diamond 86')
            ->assertJsonPath('data.0.selling_price', 11_000)
            ->assertJsonPath('data.0.reseller_price', 10_450)
            ->assertJsonPath('data.0.discount', 550)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 50)
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_product_list_and_detail_match_the_documented_contract(): void
    {
        config(['reseller.discount_percent' => 5]);
        [, $plain] = $this->reseller();
        $product = $this->product([
            'brand_id' => null,
            'category_id' => null,
            'region' => null,
            'unlimited_stock' => true,
        ]);

        $listResponse = $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/products?per_page=10')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'code',
                    'name',
                    'brand',
                    'category',
                    'payment_type',
                    'region',
                    'selling_price',
                    'reseller_price',
                    'discount',
                    'stock',
                    'unlimited_stock',
                    'requires_zone',
                    'target_label',
                ]],
                'meta' => ['current_page', 'per_page', 'last_page', 'total'],
            ])
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonPath('meta.total', 1);

        $listedProduct = $listResponse->json('data.0');
        $this->assertSame($product->getKey(), $listedProduct['id']);
        $this->assertSame(null, $listedProduct['brand']);
        $this->assertSame(null, $listedProduct['category']);
        $this->assertSame('prabayar', $listedProduct['payment_type']);
        $this->assertSame(null, $listedProduct['region']);
        $this->assertSame(11_000, $listedProduct['selling_price']);
        $this->assertSame(10_450, $listedProduct['reseller_price']);
        $this->assertSame(550, $listedProduct['discount']);
        $this->assertSame(null, $listedProduct['stock']);
        $this->assertSame(true, $listedProduct['unlimited_stock']);
        $this->assertSame(false, $listedProduct['requires_zone']);
        $this->assertSame('Nomor Tujuan', $listedProduct['target_label']);

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/products/'.$product->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'code',
                    'name',
                    'brand',
                    'category',
                    'payment_type',
                    'region',
                    'selling_price',
                    'reseller_price',
                    'discount',
                    'stock',
                    'unlimited_stock',
                    'requires_zone',
                    'target_label',
                ],
            ])
            ->assertJsonPath('data.id', $product->getKey())
            ->assertJsonPath('data.stock', null)
            ->assertJsonPath('data.unlimited_stock', true);

        $mobileLegendsProduct = $this->product([
            'brand_id' => Brand::factory()->create(['slug' => 'mobile-legends'])->getKey(),
        ]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/products/'.$mobileLegendsProduct->getKey())
            ->assertOk()
            ->assertJsonPath('data.requires_zone', true)
            ->assertJsonPath('data.target_label', 'ID Game');
    }

    public function test_products_index_rejects_per_page_above_one_hundred(): void
    {
        [, $plain] = $this->reseller();

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/products?per_page=101')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The per page field must not be greater than 100.')
            ->assertJsonPath('errors.per_page.0', 'The per page field must not be greater than 100.');
    }

    public function test_products_can_be_filtered_by_search_term(): void
    {
        [, $plain] = $this->reseller();
        $this->product(['name' => 'Diamond 86', 'code' => 'ML86']);
        $this->product(['name' => 'Pulsa Telkomsel 5rb', 'code' => 'TSEL5']);

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/products?q=pulsa')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'TSEL5');
    }

    public function test_inactive_products_are_hidden_from_the_api(): void
    {
        [, $plain] = $this->reseller();
        $product = $this->product(['status' => false]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/products')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/products/'.$product->getKey())
            ->assertNotFound();
    }

    public function test_create_order_pays_with_balance_at_reseller_price_and_queues_fulfillment(): void
    {
        Queue::fake();

        [$user, $plain] = $this->reseller(['balance' => 100_000]);
        $product = $this->product(['provider' => Product::PROVIDER_DIGIFLAZZ, 'stock' => 5, 'unlimited_stock' => false]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [
                'product_id' => $product->getKey(),
                'target' => '12345678',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.amount', 10_450)
            ->assertJsonPath('data.payment_method', 'saldo');

        $order = Order::sole();
        $product->refresh();

        $this->assertSame('api', $order->source);
        $this->assertSame(10_450, (int) $order->amount);
        $this->assertSame(89_550, $user->fresh()->balance);
        $this->assertSame(4, $product->stock);
        $this->assertSame(1, $product->sold_count);
        Queue::assertPushed(
            SendDigiflazzTransaction::class,
            fn (SendDigiflazzTransaction $job): bool => $job->orderId === $order->getKey(),
        );
    }

    public function test_create_order_with_insufficient_balance_returns_422_and_creates_nothing(): void
    {
        [$user, $plain] = $this->reseller(['balance' => 5_000]);
        $product = $this->product(['stock' => 5]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [
                'product_id' => $product->getKey(),
                'target' => '12345678',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('balance');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5_000, $user->fresh()->balance);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_create_order_out_of_stock_returns_422(): void
    {
        [, $plain] = $this->reseller();
        $product = $this->product(['stock' => 0]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [
                'product_id' => $product->getKey(),
                'target' => '12345678',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_create_order_requires_zone_for_zone_products(): void
    {
        [, $plain] = $this->reseller();
        $product = $this->product(['brand_id' => Brand::factory()->create(['slug' => 'mobile-legends'])->getKey()]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [
                'product_id' => $product->getKey(),
                'target' => '12345678',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('zone');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_create_order_rejects_an_operator_mismatch(): void
    {
        [, $plain] = $this->reseller();
        $product = $this->product([
            'brand_id' => Brand::factory()->create(['slug' => 'telkomsel'])->getKey(),
            'category_id' => Category::factory()->create(['slug' => 'pulsa'])->getKey(),
            'name' => 'Pulsa Telkomsel 10rb',
        ]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [
                'product_id' => $product->getKey(),
                'target' => '085700001234',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('target');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_create_order_requires_the_defined_fields(): void
    {
        [, $plain] = $this->reseller();

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id', 'target']);
    }

    public function test_create_order_rejects_an_inactive_product(): void
    {
        [, $plain] = $this->reseller();
        $product = $this->product(['status' => false]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [
                'product_id' => $product->getKey(),
                'target' => '12345678',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');
    }

    public function test_orders_index_only_lists_the_resellers_own_orders(): void
    {
        [$user, $plain] = $this->reseller();
        $product = $this->product();

        Order::factory()->for($user)->create(['product_id' => $product->getKey()]);
        Order::factory()->for(User::factory()->create())->create(['product_id' => $product->getKey()]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.order_number', Order::query()->where('user_id', $user->getKey())->sole()->order_number);
    }

    public function test_order_show_returns_404_for_another_users_order(): void
    {
        [, $plain] = $this->reseller();
        $product = $this->product();
        $foreign = Order::factory()->for(User::factory()->create())->create(['product_id' => $product->getKey()]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/orders/'.$foreign->order_number)
            ->assertNotFound();
    }

    public function test_order_show_returns_the_own_order(): void
    {
        [$user, $plain] = $this->reseller();
        $product = $this->product();
        $order = Order::factory()->for($user)->create(['product_id' => $product->getKey()]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/orders/'.$order->order_number)
            ->assertOk()
            ->assertJsonPath('data.order_number', $order->order_number)
            ->assertJsonPath('data.product.name', $product->name);
    }
}
