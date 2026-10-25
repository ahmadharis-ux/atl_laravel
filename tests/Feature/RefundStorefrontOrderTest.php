<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Jobs\RefundStorefrontOrder;
use App\Models\Order;
use App\Models\User;
use App\Services\MidtransService;
use App\Support\OrderFulfillment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RefundStorefrontOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_qris_fulfillment_dispatches_a_refund_job_and_restores_stock(): void
    {
        $reseller = User::factory()->create();
        $order = $this->qrisOrder($reseller);
        $product = $order->product;
        Queue::fake([RefundStorefrontOrder::class]);

        OrderFulfillment::applyResult($order, ['status' => 'Gagal', 'message' => 'Produk gagal.']);

        $this->assertSame(OrderStatus::Failed, $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
        Queue::assertPushed(RefundStorefrontOrder::class, fn (RefundStorefrontOrder $job): bool => $job->orderId === $order->getKey());
    }

    public function test_full_midtrans_refund_marks_the_qris_order_as_refunded(): void
    {
        $order = $this->qrisOrder(User::factory()->create(), ['status' => OrderStatus::Failed]);
        $response = (object) [
            'status_code' => '200',
            'transaction_status' => 'refund',
            'refund_amount' => '12300',
            'transaction_id' => 'refund-transaction-123',
            'refund_key' => 'store-refund-'.$order->order_number,
        ];
        $midtrans = \Mockery::mock(MidtransService::class);
        $midtrans->shouldReceive('requestStorefrontRefund')
            ->once()
            ->with(\Mockery::on(fn (Order $candidate): bool => $candidate->is($order)))
            ->andReturn($response);

        (new RefundStorefrontOrder($order->getKey()))->handle($midtrans);

        $this->assertSame('completed', $order->fresh()->refund_status);
        $this->assertNotNull($order->fresh()->refunded_at);
        $this->assertSame('refund-transaction-123', $order->fresh()->refund_transaction_id);
    }

    public function test_partial_midtrans_refund_requires_manual_admin_follow_up(): void
    {
        $order = $this->qrisOrder(User::factory()->create(), ['status' => OrderStatus::Failed]);
        $response = (object) [
            'status_code' => '200',
            'transaction_status' => 'partial_refund',
            'refund_amount' => '10000',
            'transaction_id' => 'partial-refund-123',
        ];
        $midtrans = \Mockery::mock(MidtransService::class);
        $midtrans->shouldReceive('requestStorefrontRefund')->once()->andReturn($response);

        (new RefundStorefrontOrder($order->getKey()))->handle($midtrans);

        $this->assertSame('manual_required', $order->fresh()->refund_status);
        $this->assertNull($order->fresh()->refunded_at);
    }

    private function qrisOrder(User $reseller, array $attributes = []): Order
    {
        $order = Order::factory()->for($reseller)->create(array_merge([
            'status' => OrderStatus::Processing,
            'payment_method' => PaymentMethod::Qris,
            'amount' => 12_000,
            'fee' => 300,
            'source' => 'store_qris',
            'provider' => 'digiflazz',
            'metadata' => [
                'storefront' => [
                    'selling_price' => 12_000,
                    'reseller_price' => 10_800,
                    'payment_method' => 'qris',
                ],
            ],
        ], $attributes));

        $order->product->update(['stock' => 4, 'unlimited_stock' => false]);

        return $order->fresh();
    }
}
