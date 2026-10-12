<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpirePendingOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unpaid_order_past_its_ttl_is_expired_and_stock_is_returned(): void
    {
        $product = Product::factory()->create([
            'unlimited_stock' => false,
            'stock' => 0,
            'sold_count' => 1,
        ]);

        $order = Order::factory()->create([
            'user_id' => User::factory(),
            'product_id' => $product->getKey(),
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::VirtualAccount,
            'paid_at' => null,
            'created_at' => Carbon::now()->subHours(4),
        ]);

        $this->artisan('orders:expire-pending')->assertSuccessful();

        $order->refresh();

        $this->assertSame(OrderStatus::Expired, $order->status);
        $this->assertNull($order->refunded_at);
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame(0, $product->fresh()->sold_count);
    }

    public function test_a_recent_pending_order_is_left_alone(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::VirtualAccount,
            'paid_at' => null,
            'created_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->artisan('orders:expire-pending')->assertSuccessful();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_a_paid_order_is_never_expired(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Processing,
            'provider' => Product::PROVIDER_DIGIFLAZZ,
            'payment_method' => PaymentMethod::Saldo,
            'paid_at' => Carbon::now(),
            'created_at' => Carbon::now()->subHours(4),
        ]);

        $this->artisan('orders:expire-pending')->assertSuccessful();

        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
    }
}
