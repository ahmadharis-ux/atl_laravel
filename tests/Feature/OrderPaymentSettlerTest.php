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
        $this->assertSame(24, (int) $reseller->fresh()->points);
        $this->assertSame(24, (int) $reseller->fresh()->lifetime_points);
        $this->assertDatabaseCount('wallet_transactions', 0);

        $refundNotification = [
            'order_id' => $order->order_number,
            'transaction_status' => 'refund',
            'refund_amount' => (string) $order->totalDue(),
        ];
        $midtrans->handleNotification($refundNotification);
        $midtrans->handleNotification($refundNotification);

        $this->assertSame(0, (int) $reseller->fresh()->points);
        $this->assertSame(0, (int) $reseller->fresh()->lifetime_points);
        $this->assertSame(1, $reseller->pointTransactions()->where('type', 'order_reward_reversed')->count());

        $this->actingAs($reseller)
            ->get(route('orders.show', $order))
            ->assertSee('Pengembalian dana telah selesai')
            ->assertSee('Reward dibalik karena refund')
            ->assertSee('-24 poin');
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
        $this->assertSame(24, (int) $reseller->fresh()->points);
    }

    public function test_partial_refund_keeps_reward_until_refund_is_confirmed_complete(): void
    {
        $reseller = $this->reseller();
        $order = $this->storefrontOrder($reseller);
        $midtrans = app(MidtransService::class);

        $midtrans->handleNotification([
            'order_id' => $order->order_number,
            'transaction_status' => 'settlement',
        ]);
        $midtrans->handleNotification([
            'order_id' => $order->order_number,
            'transaction_status' => 'partial_refund',
            'refund_amount' => '5000',
        ]);

        $this->assertSame('manual_required', $order->fresh()->refund_status);
        $this->assertNull($order->fresh()->refunded_at);
        $this->assertSame(24, (int) $reseller->fresh()->points);
        $this->assertDatabaseCount('point_transactions', 1);

        $this->actingAs($reseller)
            ->get(route('orders.show', $order))
            ->assertSee('Refund memerlukan bantuan CS')
            ->assertDontSee('Pengembalian dana telah selesai');
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
            'supplier_cost_snapshot' => 10_000,
            'company_cost_snapshot' => 1_200,
            'reward_eligible' => true,
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
