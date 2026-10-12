<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionType;
use App\Jobs\SendDigiflazzTransaction;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Support\OrderFulfillment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DigiflazzFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(array $attributes = []): Product
    {
        $category = Category::factory()->create(['slug' => 'games']);

        return Product::factory()->create(array_merge([
            'status' => true,
            'provider' => Product::PROVIDER_DIGIFLAZZ,
            'code' => 'ML-86',
            'category_id' => $category->id,
            'cost_price' => 10_000,
            'markup_value' => 0,
            'unlimited_stock' => true,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function order(array $attributes = []): Order
    {
        return Order::factory()->create(array_merge([
            'amount' => 10_000,
            'status' => OrderStatus::Processing,
            'provider' => Product::PROVIDER_DIGIFLAZZ,
            'provider_ref' => 'TRX-REF-'.fake()->unique()->numerify('#####'),
            'payment_method' => PaymentMethod::Saldo,
        ], $attributes));
    }

    public function test_fulfilled_checkout_dispatches_the_job_and_goes_to_processing(): void
    {
        Queue::fake();

        $user = User::factory()->create(['balance' => 1_000_000]);
        $product = $this->product();

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertRedirect();

        $order = Order::query()->sole();

        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame(Product::PROVIDER_DIGIFLAZZ, $order->provider);
        $this->assertSame($order->order_number, $order->provider_ref);
        $this->assertSame(990_000, $user->fresh()->balance);

        Queue::assertPushed(
            SendDigiflazzTransaction::class,
            fn (SendDigiflazzTransaction $job): bool => $job->orderId === $order->getKey(),
        );
    }

    public function test_the_job_marks_the_order_successful_and_stores_the_sn(): void
    {
        Http::fake([
            'api.digiflazz.com/*' => Http::response(['data' => [
                'status' => 'Sukses',
                'rc' => '00',
                'sn' => 'SN-123',
                'price' => 10_000,
                'admin' => 200,
            ]]),
        ]);

        $order = $this->order();

        (new SendDigiflazzTransaction($order->getKey()))->handle();

        $order->refresh();

        $this->assertSame(OrderStatus::Success, $order->status);
        $this->assertSame('SN-123', $order->provider_sn);
        $this->assertNotNull($order->processed_at);
        $this->assertSame(200, $order->provider_admin);
    }

    public function test_a_failed_transaction_refunds_balance_and_restores_stock(): void
    {
        Http::fake([
            'api.digiflazz.com/*' => Http::response(['data' => [
                'status' => 'Gagal',
                'message' => 'Nomor tujuan salah',
                'rc' => '14',
            ]]),
        ]);

        $user = User::factory()->create(['balance' => 90_000]);
        $product = $this->product(['unlimited_stock' => false, 'stock' => 0, 'sold_count' => 1]);
        $order = $this->order(['user_id' => $user->getKey(), 'product_id' => $product->getKey()]);

        (new SendDigiflazzTransaction($order->getKey()))->handle();

        $order->refresh();

        $this->assertSame(OrderStatus::Failed, $order->status);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame(100_000, $user->fresh()->balance);
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame(0, $product->fresh()->sold_count);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->getKey(),
            'type' => WalletTransactionType::Refund->value,
            'direction' => WalletTransactionDirection::Credit->value,
            'amount' => 10_000,
            'reference_id' => $order->getKey(),
        ]);
    }

    public function test_webhook_validates_the_signature_and_finalizes_the_order(): void
    {
        config()->set('services.digiflazz.webhook_secret', 'secret');

        $order = $this->order(['provider_ref' => 'TRX-WEBHOOK-1']);

        $payload = json_encode(['data' => [[
            'ref_id' => 'TRX-WEBHOOK-1',
            'status' => 'Sukses',
            'sn' => 'SN-WEBHOOK-1',
        ]]]);

        $this->call('POST', route('webhook.digiflazz'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Hub-Signature' => 'sha1=wrong',
        ], $payload)->assertStatus(401);

        $this->call('POST', route('webhook.digiflazz'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Hub-Signature' => 'sha1='.hash_hmac('sha1', $payload, 'secret'),
        ], $payload)->assertStatus(200);

        $this->assertSame(OrderStatus::Success, $order->fresh()->status);
        $this->assertSame('SN-WEBHOOK-1', $order->fresh()->provider_sn);
    }

    public function test_a_repeated_failure_webhook_refunds_only_once(): void
    {
        $user = User::factory()->create(['balance' => 90_000]);
        $order = $this->order([
            'user_id' => $user->getKey(),
            'provider_ref' => 'TRX-DOUBLE-1',
        ]);

        $payload = ['ref_id' => 'TRX-DOUBLE-1', 'status' => 'Gagal', 'message' => 'stok habis'];

        OrderFulfillment::applyResult($order, $payload);
        OrderFulfillment::applyResult($order->fresh(), $payload);

        $order->refresh();

        $this->assertSame(OrderStatus::Failed, $order->status);
        $this->assertSame(100_000, $user->fresh()->balance);
        $this->assertSame(1, WalletTransaction::query()
            ->where('type', WalletTransactionType::Refund)
            ->count());
    }

    public function test_a_pending_va_order_dispatches_the_job_once_paid(): void
    {
        Queue::fake();

        $user = User::factory()->create(['balance' => 1_000_000]);
        $order = $this->order([
            'user_id' => $user->getKey(),
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::VirtualAccount,
            'payment_channel' => 'bca',
            'paid_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('orders.pay', $order))
            ->assertRedirect();

        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);

        Queue::assertPushed(SendDigiflazzTransaction::class);
    }
}
