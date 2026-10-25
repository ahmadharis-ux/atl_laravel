<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductPaymentType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\MidtransService;
use App\Support\OrderFulfillment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['reseller.discount_percent' => 10]);
    }

    public function test_storefront_lists_only_available_prepaid_products(): void
    {
        $reseller = $this->reseller();
        $prepaid = $this->product(['name' => 'Diamond Prabayar']);
        $postpaid = $this->product([
            'name' => 'Tagihan Pascabayar',
            'payment_type' => ProductPaymentType::Pascabayar,
        ]);

        $this->get(route('storefront.index', $reseller))
            ->assertSee('Diamond Prabayar')
            ->assertDontSee('Tagihan Pascabayar');

        $this->get(route('storefront.product', [$reseller, $postpaid]))
            ->assertNotFound();

        $this->assertSame(ProductPaymentType::Prabayar, $prepaid->payment_type);
    }

    public function test_checkout_requires_a_zone_for_games_that_need_one(): void
    {
        $reseller = $this->reseller();
        $product = $this->product([
            'brand_id' => Brand::factory()->create(['slug' => 'mobile-legends'])->getKey(),
        ]);

        $this->post(route('storefront.checkout', [$reseller, $product]), [
            'target' => '123456789',
            'payment_method' => 'cash',
            'storefront_key' => Str::random(48),
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('zone');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_cash_checkout_reserves_stock_and_debits_balance_only_after_confirmation(): void
    {
        $reseller = $this->reseller(['balance' => 50_000]);
        $product = $this->product(['stock' => 5, 'unlimited_stock' => false]);
        $key = Str::random(48);

        $response = $this->post(route('storefront.checkout', [$reseller, $product]), [
            'target' => '123456789',
            'payment_method' => 'cash',
            'storefront_key' => $key,
        ]);

        $order = Order::query()->sole();
        $response->assertRedirect(route('storefront.orders.show', [
            'order' => $order->getKey(),
            'token' => $key,
        ]));

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame('store_cash', $order->source);
        $this->assertSame(10_800, $order->amount);
        $this->assertSame(12_000, $order->metadata['storefront']['selling_price']);
        $this->assertSame(50_000, $reseller->fresh()->balance);
        $this->assertSame(4, $product->fresh()->stock);
        $this->assertDatabaseCount('wallet_transactions', 0);

        $this->get(route('storefront.orders.show', ['order' => $order->getKey(), 'token' => $key]))
            ->assertOk()
            ->assertSee('Menunggu konfirmasi reseller')
            ->assertSee('Rp 12.000');

        $this->actingAs($reseller)
            ->from(route('settings.reseller.store'))
            ->post(route('settings.reseller.store.cash.confirm', $order))
            ->assertRedirect(route('settings.reseller.store'))
            ->assertSessionHas('status');

        $this->assertSame(OrderStatus::Success, $order->fresh()->status);
        $this->assertSame(39_200, $reseller->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $reseller->getKey(),
            'reference_id' => $order->getKey(),
            'type' => 'purchase',
            'direction' => 'debit',
            'amount' => 10_800,
        ]);
        $this->assertDatabaseCount('reseller_commissions', 0);
    }

    public function test_cash_confirmation_keeps_order_pending_when_reseller_balance_is_insufficient(): void
    {
        $reseller = $this->reseller(['balance' => 100]);
        $product = $this->product(['stock' => 5, 'unlimited_stock' => false]);

        $this->post(route('storefront.checkout', [$reseller, $product]), [
            'target' => '123456789',
            'payment_method' => 'cash',
            'storefront_key' => Str::random(48),
        ])->assertRedirect();

        $order = Order::query()->sole();

        $this->actingAs($reseller)
            ->from(route('settings.reseller.store'))
            ->post(route('settings.reseller.store.cash.confirm', $order))
            ->assertRedirect(route('settings.reseller.store'))
            ->assertSessionHasErrors('balance');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(100, $reseller->fresh()->balance);
        $this->assertSame(4, $product->fresh()->stock);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_cash_confirmation_cannot_debit_the_reseller_twice(): void
    {
        $reseller = $this->reseller(['balance' => 50_000]);
        $product = $this->product();

        $this->post(route('storefront.checkout', [$reseller, $product]), [
            'target' => '123456789',
            'payment_method' => 'cash',
            'storefront_key' => Str::random(48),
        ])->assertRedirect();

        $order = Order::query()->sole();

        $this->actingAs($reseller)
            ->post(route('settings.reseller.store.cash.confirm', $order))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->actingAs($reseller)
            ->from(route('settings.reseller.store'))
            ->post(route('settings.reseller.store.cash.confirm', $order))
            ->assertRedirect(route('settings.reseller.store'))
            ->assertSessionHasErrors('order');

        $this->assertSame(39_200, $reseller->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_pending_cash_order_with_recorded_confirmation_is_not_expired(): void
    {
        $reseller = $this->reseller();
        $product = $this->product(['stock' => 4, 'unlimited_stock' => false]);
        $order = Order::factory()->for($reseller)->for($product)->create([
            'status' => OrderStatus::Pending,
            'source' => 'store_cash',
            'expires_at' => now()->subMinute(),
            'metadata' => [
                'storefront' => [
                    'payment_method' => 'cash',
                    'cash_confirmed_at' => now()->toIso8601String(),
                ],
            ],
        ]);

        OrderFulfillment::expire($order);

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_repeated_cash_checkout_with_the_same_key_does_not_reserve_stock_twice(): void
    {
        $reseller = $this->reseller();
        $product = $this->product(['stock' => 5, 'unlimited_stock' => false]);
        $key = Str::random(48);
        $payload = [
            'target' => '123456789',
            'payment_method' => 'cash',
            'storefront_key' => $key,
        ];

        $this->post(route('storefront.checkout', [$reseller, $product]), $payload)->assertRedirect();
        $this->post(route('storefront.checkout', [$reseller, $product]), $payload)
            ->assertRedirect(route('storefront.orders.show', [
                'order' => Order::query()->sole()->getKey(),
                'token' => $key,
            ]));

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_qris_checkout_creates_a_hosted_order_without_debiting_reseller_balance(): void
    {
        $reseller = $this->reseller(['balance' => 50_000]);
        $product = $this->product();
        $midtrans = \Mockery::mock(MidtransService::class);
        $midtrans->shouldReceive('isConfigured')->once()->andReturn(true);
        $midtrans->shouldReceive('qrisFee')->once()->with(12_000)->andReturn(300);
        $midtrans->shouldReceive('chargeStorefrontOrder')
            ->once()
            ->with(\Mockery::type(Order::class), \Mockery::type('string'))
            ->andReturn('https://payments.example.test/snap/hosted-checkout');
        $this->app->instance(MidtransService::class, $midtrans);

        $this->post(route('storefront.checkout', [$reseller, $product]), [
            'target' => '123456789',
            'payment_method' => 'qris',
            'storefront_key' => Str::random(48),
        ])->assertRedirect('https://payments.example.test/snap/hosted-checkout');

        $order = Order::query()->sole();

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentMethod::Qris, $order->payment_method);
        $this->assertSame('store_qris', $order->source);
        $this->assertSame(12_000, $order->amount);
        $this->assertSame(300, $order->fee);
        $this->assertSame(50_000, $reseller->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
        $this->assertDatabaseCount('reseller_commissions', 0);
    }

    public function test_order_status_requires_the_correct_bearer_token(): void
    {
        $reseller = $this->reseller();
        $order = Order::factory()->for($reseller)->create([
            'source' => 'store_qris',
            'storefront_key' => Str::random(48),
        ]);

        $this->get(route('storefront.orders.show', [
            'order' => $order->getKey(),
            'token' => Str::random(48),
        ]))->assertNotFound();
    }

    public function test_inactive_reseller_cannot_open_the_storefront(): void
    {
        $reseller = $this->reseller(['is_reseller' => false]);

        $this->get(route('storefront.index', $reseller))->assertNotFound();
    }

    private function reseller(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'is_reseller' => true,
            'reseller_since' => now(),
            'balance' => 50_000,
        ], $attributes));
    }

    private function product(array $attributes = []): Product
    {
        $category = Category::factory()->create(['slug' => 'games-'.Str::lower(Str::random(6))]);

        return Product::factory()->create(array_merge([
            'status' => true,
            'category_id' => $category->getKey(),
            'payment_type' => ProductPaymentType::Prabayar,
            'cost_price' => 10_000,
            'markup_value' => 2_000,
            'stock' => 10,
            'unlimited_stock' => true,
        ], $attributes));
    }
}
