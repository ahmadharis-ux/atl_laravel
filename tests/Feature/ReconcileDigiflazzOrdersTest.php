<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReconcileDigiflazzOrdersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function order(array $attributes = []): Order
    {
        return Order::factory()->create(array_merge([
            'amount' => 10_000,
            'status' => OrderStatus::Processing,
            'provider' => Product::PROVIDER_DIGIFLAZZ,
            'provider_ref' => 'TRX-REC-'.fake()->unique()->numerify('#####'),
            'payment_method' => PaymentMethod::Saldo,
            'processed_at' => Carbon::now()->subMinutes(20),
        ], $attributes));
    }

    public function test_an_old_processing_order_is_resent_and_finalized(): void
    {
        Http::fake([
            'api.digiflazz.com/*' => Http::response(['data' => [
                'status' => 'Sukses',
                'rc' => '00',
                'sn' => 'SN-REC-1',
            ]]),
        ]);

        $order = $this->order();

        $this->artisan('digiflazz:reconcile-orders')->assertSuccessful();

        $order->refresh();

        $this->assertSame(OrderStatus::Success, $order->status);
        $this->assertSame('SN-REC-1', $order->provider_sn);
    }

    public function test_an_order_still_pending_at_the_provider_stays_processing(): void
    {
        Http::fake([
            'api.digiflazz.com/*' => Http::response(['data' => [
                'status' => 'Pending',
            ]]),
        ]);

        $order = $this->order();

        $this->artisan('digiflazz:reconcile-orders')->assertSuccessful();

        $order->refresh();

        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame('Pending', $order->provider_status);
    }

    public function test_a_recent_order_is_not_reconciled_yet(): void
    {
        Http::fake();

        $this->order(['processed_at' => Carbon::now()->subMinutes(2)]);

        $this->artisan('digiflazz:reconcile-orders')->assertSuccessful();

        Http::assertNothingSent();
    }
}
