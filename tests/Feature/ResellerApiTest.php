<?php

namespace Tests\Feature;

use App\Jobs\SendDigiflazzTransaction;
use App\Jobs\SendResellerWebhook;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderFulfillment;
use App\Support\ResellerKey;
use App\Support\ResellerWebhook;
use App\Support\ResellerWebhookAddressResolver;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ResellerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('X-Transaction-PIN', UserFactory::DEFAULT_PIN);
    }

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
            'provider' => Product::PROVIDER_DIGIFLAZZ,
            'category_id' => $category->getKey(),
            'cost_price' => 10_000,
            'markup_value' => 1_000,
            'unlimited_stock' => false,
            'stock' => 5,
        ], $attributes));
    }

    private function publicWebhookAddressResolver(): ResellerWebhookAddressResolver
    {
        $resolver = \Mockery::mock(ResellerWebhookAddressResolver::class);
        $resolver->shouldReceive('resolvePublicAddresses')
            ->with('callback.example.test')
            ->andReturn(['93.184.216.34']);
        $resolver->shouldReceive('curlResolveEntries')
            ->with('callback.example.test', 443, ['93.184.216.34'])
            ->andReturn(['callback.example.test:443:93.184.216.34']);

        return $resolver;
    }

    public function test_request_without_an_api_key_is_unauthorized(): void
    {
        $this->getJson('/api/v1/reseller/profile')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'API key tidak ditemukan. Sertakan header X-API-Key atau Authorization: Bearer.');
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

    public function test_suspended_resellers_key_is_rejected(): void
    {
        [, $plain] = $this->reseller(['reseller_status' => 'suspended']);

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
        $this->assertSame(10_000, $order->supplier_cost_snapshot);
        $this->assertSame(0, $order->company_cost_snapshot);
        $this->assertTrue($order->reward_eligible);
        $this->assertSame(89_550, $user->fresh()->balance);
        $this->assertSame(4, $product->stock);
        $this->assertSame(1, $product->sold_count);
        Queue::assertPushed(
            SendDigiflazzTransaction::class,
            fn (SendDigiflazzTransaction $job): bool => $job->orderId === $order->getKey(),
        );
    }

    public function test_create_order_replays_the_same_reff_id_without_debiting_twice(): void
    {
        Queue::fake();

        [$user, $plain] = $this->reseller(['balance' => 100_000]);
        $product = $this->product(['stock' => 5]);
        $payload = [
            'product_id' => $product->getKey(),
            'target' => '081234567890',
            'reff_id' => 'order-0001',
        ];

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', $payload)
            ->assertCreated()
            ->assertJsonPath('data.reff_id', 'order-0001');

        $firstOrderNumber = Order::sole()->order_number;

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', $payload)
            ->assertOk()
            ->assertJsonPath('data.order_number', $firstOrderNumber)
            ->assertJsonPath('data.reff_id', 'order-0001');

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(89_550, $user->fresh()->balance);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_create_order_accepts_legacy_product_code_and_combined_zone_target(): void
    {
        Queue::fake();

        [, $plain] = $this->reseller();
        $product = $this->product([
            'brand_id' => Brand::factory()->create(['slug' => 'mobile-legends'])->getKey(),
            'code' => 'ML-DIAMOND-86',
        ]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [
                'code' => $product->code,
                'target' => '12345678|1234',
                'reff_id' => 'legacy-zone-01',
            ])
            ->assertCreated()
            ->assertJsonPath('data.product.code', 'ML-DIAMOND-86')
            ->assertJsonPath('data.target', '12345678')
            ->assertJsonPath('data.target_zone', '1234')
            ->assertJsonPath('data.reff_id', 'legacy-zone-01');

        $this->assertDatabaseHas('orders', [
            'product_id' => $product->getKey(),
            'target' => '12345678',
            'target_zone' => '1234',
            'idempotency_key' => 'legacy-zone-01',
        ]);
    }

    public function test_create_order_rejects_a_price_above_limit_price_without_charging(): void
    {
        [$user, $plain] = $this->reseller(['balance' => 100_000]);
        $product = $this->product();

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [
                'product_id' => $product->getKey(),
                'target' => '081234567890',
                'limit_price' => 10_449,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit_price');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(100_000, $user->fresh()->balance);
    }

    public function test_products_and_orders_exclude_unsupported_provider_products(): void
    {
        [, $plain] = $this->reseller();
        $product = $this->product(['provider' => null]);

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/products')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->withHeaders(['X-API-Key' => $plain])
            ->getJson('/api/v1/reseller/products/'.$product->getKey())
            ->assertNotFound();

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [
                'product_id' => $product->getKey(),
                'target' => '081234567890',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_legacy_h2h_endpoints_support_price_list_create_and_status_polling(): void
    {
        Queue::fake();

        [$user, $plain] = $this->reseller(['balance' => 100_000]);
        $product = $this->product([
            'brand_id' => Brand::factory()->create(['slug' => 'mobile-legends'])->getKey(),
            'code' => 'ML-LEGACY-86',
        ]);

        $this->post('/layanan/price_list', [
            'api_key' => $plain,
            'type' => 'prabayar',
        ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.0.code', 'ML-LEGACY-86')
            ->assertJsonPath('data.0.price', 10_450);

        $created = $this->post('/transaksi/create', [
            'api_key' => $plain,
            'code' => 'ML-LEGACY-86',
            'reff_id' => 'old-h2h-0001',
            'target' => '12345678|1234',
            'limit_price' => 10_500,
        ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.reff_id', 'old-h2h-0001')
            ->assertJsonPath('data.code', 'ML-LEGACY-86')
            ->assertJsonPath('data.target', '12345678|1234')
            ->assertJsonPath('data.price', 10_450)
            ->assertJsonPath('code', 200);

        $orderNumber = $created->json('data.id');

        $this->post('/transaksi/create', [
            'api_key' => $plain,
            'code' => 'ML-LEGACY-86',
            'reff_id' => 'old-h2h-0001',
            'target' => '12345678|1234',
            'limit_price' => 10_500,
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $orderNumber);

        $this->post('/transaksi/status', [
            'api_key' => $plain,
            'id' => $orderNumber,
            'type' => 'prabayar',
        ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.id', $orderNumber)
            ->assertJsonPath('data.status', 'processing');

        $this->assertSame(89_550, $user->fresh()->balance);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_reseller_api_key_in_the_body_is_accepted_only_on_legacy_routes(): void
    {
        [, $plain] = $this->reseller();

        $this->postJson('/api/v1/reseller/orders', [
            'api_key' => $plain,
            'product_id' => $this->product()->getKey(),
            'target' => '081234567890',
        ])
            ->assertUnauthorized();
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

    public function test_webhook_job_sends_a_signed_status_notification(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'callback.example.test/*' => Http::response([], 204),
        ]);

        [$user] = $this->reseller([
            'reseller_status' => 'active',
            'reseller_webhook_url' => 'https://callback.example.test/orders',
            'reseller_webhook_secret' => 'whsec_test-secret',
        ]);
        $product = $this->product();
        $order = Order::factory()->for($user)->for($product)->create([
            'source' => 'api',
            'status' => 'success',
        ]);
        $job = new SendResellerWebhook($order->getKey(), ResellerWebhook::EVENT_SUCCESS);

        $job->handle($this->publicWebhookAddressResolver());

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://callback.example.test/orders'
            && $request->hasHeader(ResellerWebhook::EVENT_HEADER, ResellerWebhook::EVENT_SUCCESS)
            && $request->hasHeader(ResellerWebhook::DELIVERY_HEADER, $job->deliveryId)
            && $request->hasHeader(
                ResellerWebhook::SIGNATURE_HEADER,
                ResellerWebhook::signature($request->body(), 'whsec_test-secret')
            ));

        $this->assertSame('sent', $user->fresh()->reseller_webhook_last_status);
    }

    public function test_completed_api_order_dispatches_a_reseller_webhook(): void
    {
        Queue::fake([SendResellerWebhook::class]);

        [$user] = $this->reseller([
            'reseller_status' => 'active',
            'reseller_webhook_url' => 'https://callback.example.test/orders',
            'reseller_webhook_secret' => 'whsec_test-secret',
        ]);
        $product = $this->product();
        $order = Order::factory()->for($user)->for($product)->create([
            'source' => 'api',
            'status' => 'processing',
        ]);

        OrderFulfillment::applyResult($order, ['status' => 'Sukses']);

        Queue::assertPushed(
            SendResellerWebhook::class,
            fn (SendResellerWebhook $job): bool => $job->orderId === $order->getKey()
                && $job->event === ResellerWebhook::EVENT_SUCCESS
        );
        $this->assertSame('success', $order->fresh()->status->value);
    }

    public function test_webhook_is_rejected_when_dns_has_no_public_addresses(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'callback.example.test/*' => Http::response([], 204),
        ]);

        [$user] = $this->reseller([
            'reseller_status' => 'active',
            'reseller_webhook_url' => 'https://callback.example.test/orders',
            'reseller_webhook_secret' => 'whsec_test-secret',
        ]);
        $product = $this->product();
        $order = Order::factory()->for($user)->for($product)->create([
            'source' => 'api',
            'status' => 'success',
        ]);
        $resolver = \Mockery::mock(ResellerWebhookAddressResolver::class);
        $resolver->shouldReceive('resolvePublicAddresses')
            ->with('callback.example.test')
            ->once()
            ->andReturn(null);

        (new SendResellerWebhook($order->getKey(), ResellerWebhook::EVENT_SUCCESS))->handle($resolver);

        Http::assertSentCount(0);
        $this->assertSame('invalid_url', $user->fresh()->reseller_webhook_last_status);
    }

    public function test_webhook_address_resolver_rejects_private_ip_literals_and_formats_pinned_addresses(): void
    {
        $resolver = new ResellerWebhookAddressResolver;

        $this->assertSame(['93.184.216.34'], $resolver->resolvePublicAddresses('93.184.216.34'));
        $this->assertNull($resolver->resolvePublicAddresses('127.0.0.1'));
        $this->assertSame(
            ['callback.example.test:443:93.184.216.34', 'callback.example.test:443:[2606:4700:4700::1111]'],
            $resolver->curlResolveEntries('callback.example.test', 443, [
                '93.184.216.34',
                '2606:4700:4700::1111',
            ]),
        );
    }

    public function test_webhook_address_resolver_rejects_private_dns_and_mixed_public_private_answers(): void
    {
        $resolver = new class extends ResellerWebhookAddressResolver
        {
            protected function lookupDnsRecords(string $host): array|false
            {
                return match ($host) {
                    'private.example.test' => [
                        ['type' => 'A', 'ip' => '10.0.0.5'],
                    ],
                    'mixed.example.test' => [
                        ['type' => 'A', 'ip' => '93.184.216.34'],
                        ['type' => 'AAAA', 'ipv6' => 'fd00::5'],
                    ],
                    'public.example.test' => [
                        ['type' => 'A', 'ip' => '93.184.216.34'],
                        ['type' => 'AAAA', 'ipv6' => '2606:4700:4700::1111'],
                    ],
                };
            }
        };

        $this->assertNull($resolver->resolvePublicAddresses('private.example.test'));
        $this->assertNull($resolver->resolvePublicAddresses('mixed.example.test'));
        $this->assertSame(
            ['93.184.216.34', '2606:4700:4700::1111'],
            $resolver->resolvePublicAddresses('public.example.test'),
        );
    }

    public function test_create_order_requires_a_transaction_pin_header(): void
    {
        Queue::fake();

        [, $plain] = $this->reseller();
        $product = $this->product();

        $this->withHeader('X-Transaction-PIN', '')
            ->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [
                'product_id' => $product->getKey(),
                'target' => '081234567890',
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'PIN transaksi diperlukan untuk membuat pesanan.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_create_order_rejects_an_invalid_pin_and_reports_remaining_attempts(): void
    {
        Queue::fake();

        [$user, $plain] = $this->reseller();
        $product = $this->product();

        $this->withHeader('X-Transaction-PIN', '111111')
            ->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [
                'product_id' => $product->getKey(),
                'target' => '081234567890',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('remaining_attempts', 4)
            ->assertJsonValidationErrors('pin');

        $this->assertSame(1, $user->fresh()->pin_attempts);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_create_order_returns_locked_response_after_too_many_failed_attempts(): void
    {
        Queue::fake();

        [$user, $plain] = $this->reseller(['pin_attempts' => 4]);
        $product = $this->product();

        $response = $this->withHeader('X-Transaction-PIN', '111111')
            ->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [
                'product_id' => $product->getKey(),
                'target' => '081234567890',
            ])
            ->assertStatus(423)
            ->assertJsonStructure(['message', 'retry_after_seconds'])
            ->assertHeader('Retry-After');

        $this->assertGreaterThan(0, $response->json('retry_after_seconds'));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_create_order_requires_a_valid_pin_even_when_replaying_an_idempotency_key(): void
    {
        Queue::fake();

        [, $plain] = $this->reseller(['balance' => 100_000]);
        $product = $this->product();
        $payload = [
            'product_id' => $product->getKey(),
            'target' => '081234567890',
            'reff_id' => 'pin-replay-01',
        ];

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', $payload)
            ->assertCreated();

        $this->withHeader('X-Transaction-PIN', '')
            ->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', $payload)
            ->assertForbidden();

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_create_order_blocks_high_value_orders_after_a_pin_reset(): void
    {
        Queue::fake();

        [$user, $plain] = $this->reseller([
            'balance' => 1_000_000,
            'pin_reset_at' => now()->subMinute(),
        ]);
        $product = $this->product(['cost_price' => 600_000, 'markup_value' => 0]);

        $response = $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/reseller/orders', [
                'product_id' => $product->getKey(),
                'target' => '081234567890',
            ])
            ->assertStatus(423)
            ->assertJsonStructure(['message', 'retry_after_seconds'])
            ->assertHeader('Retry-After');

        $this->assertGreaterThan(0, $response->json('retry_after_seconds'));
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1_000_000, $user->fresh()->balance);
    }

    public function test_legacy_h2h_create_requires_a_transaction_pin_header(): void
    {
        Queue::fake();

        [, $plain] = $this->reseller();
        $product = $this->product();

        $this->withHeader('X-Transaction-PIN', '')
            ->withHeaders(['X-API-Key' => $plain])
            ->post('/transaksi/create', [
                'code' => $product->code,
                'reff_id' => 'legacy-pin-01',
                'target' => '081234567890',
            ])
            ->assertStatus(403)
            ->assertJsonPath('status', false)
            ->assertJsonPath('code', 403);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_legacy_h2h_replay_requires_a_valid_transaction_pin(): void
    {
        Queue::fake();

        [, $plain] = $this->reseller(['balance' => 100_000]);
        $product = $this->product();
        $payload = [
            'code' => $product->code,
            'reff_id' => 'legacy-pin-replay-01',
            'target' => '081234567890',
        ];

        $this->withHeaders(['X-API-Key' => $plain])
            ->post('/transaksi/create', $payload)
            ->assertOk();

        $this->withHeader('X-Transaction-PIN', '')
            ->withHeaders(['X-API-Key' => $plain])
            ->post('/transaksi/create', $payload)
            ->assertStatus(403)
            ->assertJsonPath('status', false)
            ->assertJsonPath('code', 403);

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_webhook_job_does_not_follow_redirects(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'callback.example.test/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/admin']),
        ]);

        [$user] = $this->reseller([
            'reseller_status' => 'active',
            'reseller_webhook_url' => 'https://callback.example.test/orders',
            'reseller_webhook_secret' => 'whsec_test-secret',
        ]);
        $product = $this->product();
        $order = Order::factory()->for($user)->for($product)->create(['source' => 'api', 'status' => 'success']);

        (new SendResellerWebhook($order->getKey(), ResellerWebhook::EVENT_SUCCESS))
            ->handle($this->publicWebhookAddressResolver());

        Http::assertSentCount(1);
        $this->assertSame('rejected', $user->fresh()->reseller_webhook_last_status);
    }
}
