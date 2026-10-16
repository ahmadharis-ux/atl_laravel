<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Jobs\SendDigiflazzTransaction;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MidtransOrderTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $category = Category::factory()->create(['slug' => 'games']);

        return Product::factory()->create([
            'status' => true,
            'category_id' => $category->id,
            'unlimited_stock' => true,
        ]);
    }

    private function configureMidtrans(): void
    {
        config()->set('midtrans.server_key', 'SB-Mid-server-test');
        config()->set('midtrans.client_key', 'SB-Mid-client-test');
        config()->set('midtrans.is_production', false);
    }

    private function notification(Order $order, string $status = 'settlement'): array
    {
        $orderId = $order->order_number;
        $statusCode = '200';
        $gross = (string) $order->amount;

        return [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $gross,
            'transaction_status' => $status,
            'fraud_status' => 'accept',
            'signature_key' => hash('sha512', $orderId.$statusCode.$gross.'SB-Mid-server-test'),
        ];
    }

    public function test_order_show_shows_qris_data_and_sandbox_testing(): void
    {
        $this->configureMidtrans();

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::Qris,
            'status' => OrderStatus::Pending,
            'metadata' => [
                'midtrans' => [
                    'qr_url' => 'https://api.sandbox.midtrans.com/v2/qris/qr.png',
                    'qr_string' => '00020101021226610014ID.CO.QRIS.WWW',
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('https://api.sandbox.midtrans.com/v2/qris/qr.png', false)
            ->assertSee('00020101021226610014ID.CO.QRIS.WWW', false)
            ->assertSee('Data Testing (Sandbox Midtrans)');
    }

    public function test_webhook_settles_order_and_starts_fulfillment_for_provider_products(): void
    {
        $this->configureMidtrans();

        Queue::fake();
        Http::fake([
            'api.digiflazz.com/*' => Http::response(['data' => [
                'status' => 'Sukses',
                'rc' => '00',
                'sn' => 'SN-123',
            ]]),
        ]);

        $user = User::factory()->create();
        $category = Category::factory()->create(['slug' => 'pulsa'.rand()]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'provider' => 'digiflazz',
            'status' => true,
            'unlimited_stock' => true,
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
            'payment_method' => PaymentMethod::Qris,
            'status' => OrderStatus::Pending,
        ]);

        $this->postJson(route('webhook.midtrans'), $this->notification($order))
            ->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertNotNull($order->paid_at);
        Queue::assertPushed(SendDigiflazzTransaction::class);
    }

    public function test_webhook_expires_order(): void
    {
        $this->configureMidtrans();

        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::Qris,
            'status' => OrderStatus::Pending,
        ]);

        $this->postJson(route('webhook.midtrans'), $this->notification($order, 'expire'))
            ->assertOk();

        $this->assertSame(OrderStatus::Expired, $order->fresh()->status);
    }

    public function test_charge_order_qris_returns_null_when_not_configured(): void
    {
        $order = Order::factory()->create();

        $service = app(MidtransService::class);
        $this->assertFalse($service->isConfigured());
        $this->assertNull($service->chargeOrderQris($order));
    }

    public function test_sync_status_route_is_hidden_in_production(): void
    {
        config()->set('midtrans.is_production', true);

        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::Qris,
            'status' => OrderStatus::Pending,
        ]);

        $this->actingAs($order->user)
            ->post(route('orders.sync', $order))
            ->assertNotFound();
    }

    public function test_sync_status_returns_null_when_not_configured(): void
    {
        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::Qris,
            'status' => OrderStatus::Pending,
        ]);

        $service = app(MidtransService::class);
        $this->assertFalse($service->isConfigured());
        $this->assertNull($service->syncOrderStatus($order));
    }
}
