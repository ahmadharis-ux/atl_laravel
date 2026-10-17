<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_detail_shows_beli_lagi_for_active_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
            'target' => '081234567890',
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Beli Lagi')
            ->assertSee(route('checkout.show', $product).'?target=081234567890', false);
    }

    public function test_beli_lagi_link_prefills_target_zone(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
            'target' => '1234567890',
            'target_zone' => '0001',
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('target=1234567890', false)
            ->assertSee('zone=0001', false);
    }

    public function test_order_detail_hides_beli_lagi_for_inactive_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => false]);
        $order = Order::factory()->create([
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('Beli Lagi');
    }
}
