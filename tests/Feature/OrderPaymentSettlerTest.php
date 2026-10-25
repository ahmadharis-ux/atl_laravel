<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Jobs\SendDigiflazzTransaction;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ResellerCommission;
use App\Models\User;
use App\Services\MidtransService;
use App\Support\OrderFulfillment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderPaymentSettlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['reseller.discount_percent' => 10]);
    }

    public function test_successful_qris_settlement_credits_commission_once(): void
    {
        $reseller = $this->reseller();
        $order = $this->storefrontOrder($reseller);
        $midtrans = app(MidtransService::class);
        $notification = [
            'order_id' => $order->order_number,
            'transaction_status' => 'settlement',
        ];

        $midtrans->handleNotification($notification);
        $midtrans->handleNotification($notification);

        $this->assertSame(OrderStatus::Success, $order->fresh()->status);
        $this->assertSame(1, ResellerCommission::query()->where('order_id', $order->getKey())->count());
        $this->assertSame(1_200, ResellerCommission::query()->where('order_id', $order->getKey())->value('amount'));
        $this->assertSame(0, $reseller->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_provider_order_earns_commission_only_after_fulfillment_succeeds(): void
    {
        $reseller = $this->reseller();
        $order = $this->storefrontOrder($reseller, ['provider' => 'digiflazz']);
        Queue::fake([SendDigiflazzTransaction::class]);

        app(MidtransService::class)->handleNotification([
            'order_id' => $order->order_number,
            'transaction_status' => 'settlement',
        ]);

        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
        $this->assertDatabaseCount('reseller_commissions', 0);
        Queue::assertPushed(SendDigiflazzTransaction::class);

        OrderFulfillment::applyResult($order->fresh(), ['status' => 'Sukses']);

        $this->assertSame(OrderStatus::Success, $order->fresh()->status);
        $this->assertSame(1, ResellerCommission::query()->where('order_id', $order->getKey())->count());
    }

    private function reseller(): User
    {
        return User::factory()->create([
            'is_reseller' => true,
            'reseller_since' => now(),
            'balance' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function storefrontOrder(User $reseller, array $attributes = []): Order
    {
        $category = Category::factory()->create(['slug' => 'games']);
        $product = Product::factory()->create([
            'category_id' => $category->getKey(),
            'status' => true,
            'cost_price' => 10_000,
            'markup_value' => 2_000,
        ]);

        return Order::factory()->for($reseller)->for($product)->create(array_merge([
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Qris,
            'amount' => 12_000,
            'fee' => 300,
            'source' => 'store_qris',
            'metadata' => [
                'storefront' => [
                    'selling_price' => 12_000,
                    'reseller_price' => 10_800,
                    'payment_method' => 'qris',
                ],
            ],
        ], $attributes));
    }
}
