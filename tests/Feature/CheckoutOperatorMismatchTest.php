<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutOperatorMismatchTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['balance' => 1000000]);
    }

    public function test_operator_mismatch_is_rejected(): void
    {
        $user = $this->user();
        $product = Product::factory()->create([
            'brand_id' => Brand::factory()->create(['slug' => 'telkomsel-'.uniqid()])->getKey(),
            'category_id' => Category::factory()->create(['slug' => 'pulsa'])->getKey(),
            'name' => 'Pulsa Telkomsel 10rb',
            'status' => true,
            'unlimited_stock' => true,
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store', $product), [
            'target' => '085700001234', // INDOSAT prefix
            'payment_method' => PaymentMethod::Saldo->value,
        ]);

        $response->assertSessionHasErrors('target');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_operator_match_is_accepted(): void
    {
        $user = $this->user();
        $product = Product::factory()->create([
            'brand_id' => Brand::factory()->create(['slug' => 'telkomsel'])->getKey(),
            'category_id' => Category::factory()->create(['slug' => 'pulsa'])->getKey(),
            'name' => 'Pulsa Telkomsel 10rb',
            'status' => true,
            'unlimited_stock' => true,
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store', $product), [
            'target' => '081200001234', // TELKOMSEL prefix
            'payment_method' => PaymentMethod::Saldo->value,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 1);
    }
}
