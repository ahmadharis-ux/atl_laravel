<?php

namespace Tests\Feature;

use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\MidtransService;
use App\Support\DepositSettler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrisAdminFeeTest extends TestCase
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
            'category_id' => $category->id,
            'cost_price' => 10_000,
            'markup_value' => 0,
            'unlimited_stock' => true,
        ], $attributes));
    }

    public function test_qris_fee_is_grossed_up_from_the_configured_percentage(): void
    {
        config()->set('midtrans.qris_fee_percent', 0.7);

        $service = app(MidtransService::class);

        $this->assertSame(71, $service->qrisFee(10_000));
        $this->assertSame(705, $service->qrisFee(100_000));
        $this->assertSame(0, $service->qrisFee(0));
    }

    public function test_qris_fee_is_zero_when_the_percentage_is_disabled(): void
    {
        config()->set('midtrans.qris_fee_percent', 0);

        $this->assertSame(0, app(MidtransService::class)->qrisFee(100_000));
    }

    public function test_qris_top_up_stores_the_admin_fee_and_credits_only_the_top_up_amount(): void
    {
        $user = User::factory()->create(['balance' => 0]);

        $this->actingAs($user)
            ->post(route('deposits.store'), [
                'amount' => 50_000,
                'payment_method' => PaymentMethod::Qris->value,
            ])
            ->assertRedirect();

        $deposit = Deposit::query()->sole();
        $expectedFee = app(MidtransService::class)->qrisFee(50_000);

        $this->assertSame($expectedFee, $deposit->fee);
        $this->assertSame(50_000 + $expectedFee, $deposit->totalDue());

        $this->assertTrue(DepositSettler::settle($deposit));

        $deposit->refresh();

        $this->assertSame(DepositStatus::Paid, $deposit->status);
        $this->assertSame(50_000 + $expectedFee, $deposit->received_amount);
        $this->assertSame(50_000, $user->fresh()->balance);
    }

    public function test_qris_checkout_stores_the_admin_fee_on_the_order(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $product = $this->product();

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::Qris->value,
            ])
            ->assertRedirect();

        $order = Order::query()->sole();
        $expectedFee = app(MidtransService::class)->qrisFee($product->selling_price);

        $this->assertSame($expectedFee, $order->fee);
        $this->assertSame($product->selling_price + $expectedFee, $order->totalDue());
        $this->assertSame(1_000_000, $user->fresh()->balance);
    }

    public function test_non_qris_methods_keep_their_own_admin_fee(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $product = $this->product();

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertRedirect();

        $this->assertSame(0, Order::query()->sole()->fee);
    }
}
