<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionLogLevel;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminTransactionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $permissions
     */
    private function admin(array $permissions = ['dashboard.view', 'transactions.view']): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create(['is_admin' => true]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

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

    public function test_regular_users_cannot_access_the_transaction_admin_pages(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $order = Order::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.transactions.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.transactions.show', $order))
            ->assertForbidden();
    }

    public function test_admin_can_see_the_transaction_list(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create(['order_number' => 'TRX-20261010-EXIST01']);

        $this->actingAs($admin)
            ->get(route('admin.transactions.index'))
            ->assertOk()
            ->assertSee('TRX-20261010-EXIST01')
            ->assertSee($order->user->name);
    }

    public function test_admin_can_view_the_transaction_invoice(): void
    {
        $admin = $this->admin();
        $product = $this->product(['name' => 'Diamond Mobile Legends']);
        $order = Order::factory()->create([
            'order_number' => 'TRX-20261010-INVOICE',
            'product_id' => $product->getKey(),
            'amount' => 25_000,
            'status' => OrderStatus::Success,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.invoice', $order))
            ->assertOk()
            ->assertSee('INVOICE')
            ->assertSee('TRX-20261010-INVOICE')
            ->assertSee('Diamond Mobile Legends')
            ->assertSee('Rp 25.000');
    }

    public function test_invoice_requires_the_view_permission(): void
    {
        $admin = $this->admin(['dashboard.view']);
        $order = Order::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.transactions.invoice', $order))
            ->assertForbidden();
    }

    public function test_transaction_list_can_be_filtered_by_status(): void
    {
        $admin = $this->admin();
        $success = Order::factory()->create([
            'order_number' => 'TRX-20261010-SUCCESS',
            'status' => OrderStatus::Success,
        ]);
        $pending = Order::factory()->create([
            'order_number' => 'TRX-20261010-PENDING',
            'status' => OrderStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.index', ['status' => OrderStatus::Success->value]))
            ->assertOk()
            ->assertSee($success->order_number)
            ->assertDontSee($pending->order_number);
    }

    public function test_transaction_detail_shows_logs_including_stack_trace(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create(['order_number' => 'TRX-20261010-DETAIL1']);

        TransactionLog::factory()->error()->create([
            'order_id' => $order->getKey(),
            'message' => 'Gagal menghubungi provider.',
            'stack_trace' => '#0 CheckoutController.php(88): handle()',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Gagal menghubungi provider.')
            ->assertSee('CheckoutController.php(88)');
    }

    public function test_balance_checkout_records_transaction_logs(): void
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

        $this->assertDatabaseHas('transaction_logs', [
            'order_id' => $order->getKey(),
            'level' => TransactionLogLevel::Info->value,
            'event' => 'order.created',
        ]);

        $this->assertDatabaseHas('transaction_logs', [
            'order_id' => $order->getKey(),
            'event' => 'order.paid',
        ]);
    }

    public function test_failed_checkout_records_a_warning_log(): void
    {
        $user = User::factory()->create(['balance' => 5_000]);
        $product = $this->product();

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::Saldo->value,
            ])
            ->assertSessionHasErrors('balance');

        $this->assertDatabaseHas('transaction_logs', [
            'level' => TransactionLogLevel::Warning->value,
            'event' => 'order.rejected',
        ]);
    }

    public function test_confirming_payment_records_a_paid_log(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $order = Order::factory()->create([
            'user_id' => $user->getKey(),
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Qris,
            'payment_code' => 'QR123456',
            'paid_at' => null,
        ]);

        $this->actingAs($user)->post(route('orders.pay', $order))->assertRedirect();

        $this->assertDatabaseHas('transaction_logs', [
            'order_id' => $order->getKey(),
            'level' => TransactionLogLevel::Info->value,
            'event' => 'order.paid',
        ]);
    }
}
