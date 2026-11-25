<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\LoyaltySetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TierPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        LoyaltySetting::current()->update([
            'gold_points' => 100,
            'platinum_points' => 500,
            'silver_discount_percent' => 0,
            'gold_discount_percent' => 10,
            'platinum_discount_percent' => 20,
        ]);
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'status' => true,
            'category_id' => Category::factory()->create(['slug' => 'games'])->id,
            'cost_price' => 10_000,
            'markup_value' => 2_000,
            'unlimited_stock' => true,
        ], $attributes));
    }

    public function test_price_follows_the_account_tier_and_never_drops_below_cost(): void
    {
        $product = $this->product();

        $this->assertSame(12_000, $product->priceFor(null));
        $this->assertSame(12_000, $product->priceFor(User::factory()->create(['lifetime_points' => 0])));
        $this->assertSame(10_800, $product->priceFor(User::factory()->create(['lifetime_points' => 150])));
        // Platinum 20% = 9.600, tapi tidak boleh di bawah modal 10.000.
        $this->assertSame(10_000, $product->priceFor(User::factory()->create(['lifetime_points' => 600])));
    }

    public function test_web_checkout_charges_the_tier_price(): void
    {
        $user = User::factory()->create(['balance' => 100_000, 'lifetime_points' => 150]);
        $product = $this->product();

        $this->actingAs($user)
            ->get(route('checkout.show', $product))
            ->assertOk()
            ->assertSee('Diskon tier Gold')
            ->assertSee('data-base-amount="10800"', false);

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::Saldo->value,
                'pin' => UserFactory::DEFAULT_PIN,
            ])
            ->assertRedirect();

        $this->assertSame(10_800, Order::query()->sole()->amount);
        $this->assertSame(100_000 - 10_800, $user->fresh()->balance);
    }

    public function test_product_list_shows_the_tier_price(): void
    {
        $this->product(['name' => 'Diamond Gold Price']);

        $this->actingAs(User::factory()->create(['lifetime_points' => 150]))
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Rp 10.800');
    }

    public function test_admin_can_set_discounts_but_not_lower_for_a_higher_tier(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = User::factory()->create(['is_admin' => true]);
        foreach (['dashboard.view', 'loyalty.view', 'loyalty.update'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $admin->givePermissionTo(['dashboard.view', 'loyalty.view', 'loyalty.update']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $payload = ['gold_points' => 100, 'platinum_points' => 500];

        $this->actingAs($admin)
            ->put(route('admin.loyalty-settings.update'), $payload + [
                'silver_discount_percent' => 1,
                'gold_discount_percent' => 3,
                'platinum_discount_percent' => 2,
            ])
            ->assertSessionHasErrors('platinum_discount_percent');

        $this->actingAs($admin)
            ->put(route('admin.loyalty-settings.update'), $payload + [
                'silver_discount_percent' => 1,
                'gold_discount_percent' => 3,
                'platinum_discount_percent' => 5.5,
            ])
            ->assertSessionHas('status');

        $this->assertSame(5.5, LoyaltySetting::current()->platinum_discount_percent);
    }
}
