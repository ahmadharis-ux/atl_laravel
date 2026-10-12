<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjaga integritas dua sumber daya yang bisa diperebutkan bersamaan:
 * stok terbatas dan saldo user (mis. dibuka di dua perangkat).
 */
class CheckoutGuardTest extends TestCase
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

    public function test_the_last_unit_cannot_be_sold_twice(): void
    {
        $product = $this->product(['unlimited_stock' => false, 'stock' => 1]);

        $first = User::factory()->create(['balance' => 1_000_000]);
        $second = User::factory()->create(['balance' => 1_000_000]);

        $this->actingAs($first)
            ->post(route('checkout.store', $product), [
                'target' => '111111111',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertRedirect();

        $this->actingAs($second)
            ->post(route('checkout.store', $product), [
                'target' => '222222222',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertSessionHasErrors('product');

        $this->assertSame(0, $product->fresh()->stock);
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1_000_000, $second->fresh()->balance);
    }

    public function test_a_balance_cannot_fund_two_purchases_from_two_devices(): void
    {
        $product = $this->product();

        $user = User::factory()->create(['balance' => 10_000]);

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '111111111',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '222222222',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertSessionHasErrors('balance');

        $this->assertSame(1, Order::query()->count());
        $this->assertSame(0, $user->fresh()->balance);
    }
}
