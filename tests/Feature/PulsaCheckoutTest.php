<?php

namespace Tests\Feature;

use App\Enums\MarkupType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Jobs\SendDigiflazzTransaction;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PulsaCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function pulsaProduct(): Product
    {
        $category = Category::factory()->create(['slug' => 'pulsa', 'name' => 'Pulsa']);
        $brand = Brand::factory()->create(['slug' => 'telkomsel', 'name' => 'Telkomsel']);

        return Product::factory()->create([
            'name' => 'Pulsa Telkomsel 10 Ribu',
            'category_id' => $category->getKey(),
            'brand_id' => $brand->getKey(),
            'status' => true,
            'provider' => Product::PROVIDER_DIGIFLAZZ,
            'code' => 'TSEL10',
            'cost_price' => 10_000,
            'markup_type' => MarkupType::Fixed,
            'markup_value' => 0,
            'unlimited_stock' => true,
        ]);
    }

    public function test_pulsa_product_checkout_page_is_accessible(): void
    {
        $product = $this->pulsaProduct();

        $this->actingAs(User::factory()->create(['balance' => 100_000]))
            ->get(route('checkout.show', $product))
            ->assertOk()
            ->assertSee('Nomor HP');
    }

    public function test_buying_pulsa_creates_a_processing_order_and_dispatches_fulfillment(): void
    {
        Queue::fake();

        $user = User::factory()->create(['balance' => 100_000]);
        $product = $this->pulsaProduct();

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '081234567890',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertRedirect();

        $order = Order::query()->sole();

        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame('081234567890', $order->target);
        $this->assertSame(Product::PROVIDER_DIGIFLAZZ, $order->provider);
        $this->assertSame($order->order_number, $order->provider_ref);
        $this->assertSame(90_000, $user->fresh()->balance);

        Queue::assertPushed(SendDigiflazzTransaction::class);
    }

    public function test_pulsa_cards_link_to_checkout_carrying_the_number(): void
    {
        $product = $this->pulsaProduct();

        $this->actingAs(User::factory()->create())
            ->get(route('pulsa.index', ['brand' => $product->brand_id]))
            ->assertOk()
            ->assertSee('data-checkout-link', false)
            ->assertSee(route('checkout.show', $product), false);
    }
}
