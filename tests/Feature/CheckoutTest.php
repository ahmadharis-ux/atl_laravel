<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MasterBank;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
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

    public function test_checkout_page_shows_the_product_and_target_label(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $product = $this->product(['name' => 'Diamond Mobile Legends']);

        $this->actingAs($user)
            ->get(route('checkout.show', $product))
            ->assertOk()
            ->assertSee('Diamond Mobile Legends')
            ->assertSee('ID Game')
            ->assertSee('Rp 10.000')
            ->assertSee('Saldo')
            ->assertSee('QRIS')
            ->assertSee('data-min-loader="2000"', false);
    }

    public function test_balance_checkout_creates_a_successful_order_and_deducts_balance(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $product = $this->product();

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertRedirect();

        $order = Order::query()->sole();

        $this->assertSame($user->getKey(), $order->user_id);
        $this->assertSame($product->getKey(), $order->product_id);
        $this->assertSame(10_000, $order->amount);
        $this->assertSame('123456789', $order->target);
        $this->assertSame(OrderStatus::Success, $order->status);
        $this->assertSame(PaymentMethod::Saldo, $order->payment_method);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(990_000, $user->fresh()->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->getKey(),
            'type' => WalletTransactionType::Purchase->value,
            'direction' => WalletTransactionDirection::Debit->value,
            'amount' => 10_000,
            'balance_before' => 1_000_000,
            'balance_after' => 990_000,
            'reference_id' => $order->getKey(),
        ]);
    }

    public function test_qris_checkout_creates_a_pending_order_without_deducting_balance(): void
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

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentMethod::Qris, $order->payment_method);
        $this->assertNotNull($order->payment_code);
        $this->assertNull($order->paid_at);
        $this->assertSame(1_000_000, $user->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_virtual_account_requires_a_master_bank_and_rejects_a_mismatched_one(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $product = $this->product();
        $bank = MasterBank::factory()->create([
            'type' => MasterBank::TYPE_BANK,
            'is_active' => true,
            'admin_fee' => 300,
        ]);
        $ewallet = MasterBank::factory()->create([
            'type' => MasterBank::TYPE_EWALLET,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::VirtualAccount->value,
            ])
            ->assertSessionHasErrors('master_bank_id');

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::VirtualAccount->value,
                'master_bank_id' => $ewallet->getKey(),
            ])
            ->assertSessionHasErrors('master_bank_id');

        $this->assertSame(0, Order::query()->count());

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::VirtualAccount->value,
                'master_bank_id' => $bank->getKey(),
            ])
            ->assertRedirect();

        $order = Order::query()->sole();
        $this->assertSame(PaymentMethod::VirtualAccount, $order->payment_method);
        $this->assertSame($bank->bank_name, $order->payment_channel);
        $this->assertSame($bank->account_number, $order->payment_code);
        $this->assertSame($bank->getKey(), $order->master_bank_id);
        $this->assertSame(300, $order->fee);
        $this->assertSame(10_300, $order->totalDue());
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertNotNull($order->expires_at);
    }

    public function test_ewallet_checkout_uses_an_active_ewallet_master_bank(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $product = $this->product();
        $ewallet = MasterBank::factory()->create([
            'type' => MasterBank::TYPE_EWALLET,
            'is_active' => true,
            'admin_fee' => 1000,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::EWallet->value,
                'master_bank_id' => $ewallet->getKey(),
            ])
            ->assertRedirect();

        $order = Order::query()->sole();
        $this->assertSame(PaymentMethod::EWallet, $order->payment_method);
        $this->assertSame($ewallet->getKey(), $order->master_bank_id);
        $this->assertSame(1000, $order->fee);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertNotNull($order->expires_at);
    }

    public function test_checkout_requires_a_target(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $product = $this->product();

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertSessionHasErrors('target');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_checkout_requires_zone_for_brands_that_use_one(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $brand = Brand::factory()->create(['slug' => 'mobile-legends']);
        $product = $this->product(['brand_id' => $brand->id]);

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertSessionHasErrors('zone');

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'zone' => '1234',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertRedirect();

        $this->assertSame('1234', Order::query()->sole()->target_zone);
    }

    public function test_balance_checkout_rejects_when_balance_is_insufficient(): void
    {
        $user = User::factory()->create(['balance' => 5_000]);
        $product = $this->product();

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertSessionHasErrors('balance');

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(5_000, $user->fresh()->balance);
    }

    public function test_pending_order_can_be_confirmed_as_paid(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $order = Order::factory()->create([
            'user_id' => $user->getKey(),
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Qris,
            'payment_code' => 'QR123456',
            'paid_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('orders.pay', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(OrderStatus::Success, $order->status);
        $this->assertNotNull($order->paid_at);
    }

    public function test_pulsa_and_data_products_can_be_checked_out(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $category = Category::factory()->create(['slug' => 'pulsa']);
        $product = Product::factory()->create(['status' => true, 'category_id' => $category->id]);

        $this->actingAs($user)->get(route('checkout.show', $product))->assertOk();
    }

    public function test_history_lists_only_the_users_own_orders(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $mine = Order::factory()->create(['user_id' => $user->getKey()]);
        $other = Order::factory()->create();

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($mine->order_number)
            ->assertDontSee($other->order_number);
    }

    public function test_a_user_cannot_view_or_pay_another_users_order(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $order = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Qris,
        ]);

        $this->actingAs($user)->get(route('orders.show', $order))->assertForbidden();
        $this->actingAs($user)->post(route('orders.pay', $order))->assertForbidden();
    }

    public function test_a_user_can_view_the_invoice_of_their_own_order(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['name' => 'Diamond Mobile Legends']);
        $order = Order::factory()->create([
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
            'target' => '123456789',
            'amount' => 50_000,
            'status' => OrderStatus::Success,
        ]);

        $this->actingAs($user)
            ->get(route('orders.invoice', $order))
            ->assertOk()
            ->assertSee('INVOICE')
            ->assertSee($order->order_number)
            ->assertSee('Diamond Mobile Legends')
            ->assertSee('Rp 50.000');
    }

    public function test_a_user_cannot_view_another_users_order_invoice(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $this->actingAs($user)->get(route('orders.invoice', $order))->assertForbidden();
    }

    public function test_product_page_submits_target_and_zone_to_checkout(): void
    {
        $user = User::factory()->create();
        $brand = Brand::factory()->create(['slug' => 'mobile-legends']);
        $product = $this->product(['brand_id' => $brand->id]);

        $this->actingAs($user)
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('action="'.route('checkout.show', $product).'"', false)
            ->assertSee('name="target"', false)
            ->assertSee('name="zone"', false);
    }

    public function test_checkout_prefills_target_and_zone_from_the_product_page(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $brand = Brand::factory()->create(['slug' => 'mobile-legends']);
        $product = $this->product(['brand_id' => $brand->id]);

        $this->actingAs($user)
            ->get(route('checkout.show', ['product' => $product, 'target' => '123456789', 'zone' => '1234']))
            ->assertOk()
            ->assertSee('value="123456789"', false)
            ->assertSee('value="1234"', false);
    }

    public function test_dashboard_shows_the_users_real_balance_after_checkout(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $product = $this->product();

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertRedirect();

        $user->refresh();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('990.000');
    }

    public function test_saldo_page_shows_the_real_balance_and_recent_orders(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $product = $this->product(['name' => 'Diamond Mobile Legends']);

        Order::factory()->create([
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
            'order_number' => 'TRX-20261010-ABCDEF',
            'amount' => 21_518,
            'status' => OrderStatus::Success,
        ]);

        $other = Order::factory()->create();

        $this->actingAs($user)
            ->get(route('saldo.index'))
            ->assertOk()
            ->assertSee('1.000.000')
            ->assertSee('TRX-20261010-ABCDEF')
            ->assertSee('Diamond Mobile Legends')
            ->assertDontSee($other->order_number);
    }
}
