<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionLogLevel;
use App\Enums\WalletTransactionType;
use App\Jobs\SyncDigiflazzTransactionStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\TransactionLog;
use App\Models\User;
use App\Support\PinVerificationResult;
use App\Support\WalletLedger;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

    public function test_storefront_cash_payment_is_not_mislabelled_as_wallet_balance(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create([
            'source' => 'store_cash',
            'payment_method' => PaymentMethod::Saldo,
            'amount' => 10_800,
            'metadata' => [
                'storefront' => [
                    'payment_method' => 'cash',
                    'selling_price' => 12_000,
                    'reseller_price' => 10_800,
                ],
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.index'))
            ->assertOk()
            ->assertSee('Tunai pelanggan')
            ->assertSee('Saldo');

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $order))
            ->assertOk()
            ->assertSee('Tunai kepada reseller')
            ->assertSee('Harga pelanggan')
            ->assertSee('Potongan saldo reseller')
            ->assertSee('Rp 10.800')
            ->assertSee('<dd class="col-sm-7 m-0 fw-semibold">Saldo</dd>', false);

        $this->actingAs($admin)
            ->get(route('admin.transactions.invoice', $order))
            ->assertOk()
            ->assertSee('Pelanggan membayar tunai kepada reseller')
            ->assertSee('Rp 10.800')
            ->assertSee('Tagihan reseller');
    }

    public function test_unpaid_expired_storefront_qris_order_cannot_be_manually_refunded(): void
    {
        $admin = $this->admin(['dashboard.view', 'transactions.view', 'transactions.update']);
        $order = Order::factory()->create([
            'source' => 'store_qris',
            'payment_method' => PaymentMethod::Qris,
            'status' => OrderStatus::Expired,
            'refund_status' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $order))
            ->assertOk()
            ->assertDontSee('Proses Refund');

        $this->actingAs($admin)
            ->from(route('admin.transactions.show', $order))
            ->post(route('admin.transactions.refund', $order))
            ->assertRedirect(route('admin.transactions.show', $order))
            ->assertSessionHas('error');

        $this->assertNull($order->fresh()->refunded_at);
    }

    public function test_admin_can_complete_a_storefront_qris_refund_marked_for_manual_handling(): void
    {
        $admin = $this->admin(['dashboard.view', 'transactions.view', 'transactions.update']);
        $order = Order::factory()->create([
            'source' => 'store_qris',
            'payment_method' => PaymentMethod::Qris,
            'status' => OrderStatus::Failed,
            'refund_status' => 'manual_required',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $order))
            ->assertOk()
            ->assertSee('Pengembalian Dana')
            ->assertSee('Proses Refund');

        $this->actingAs($admin)
            ->post(route('admin.transactions.refund', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('completed', $order->fresh()->refund_status);
        $this->assertNotNull($order->fresh()->refunded_at);
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

    public function test_admin_can_request_a_digiflazz_status_check_for_a_processing_order(): void
    {
        Queue::fake();

        $admin = $this->admin(['dashboard.view', 'transactions.view', 'transactions.update']);
        $order = Order::factory()->create([
            'status' => OrderStatus::Processing,
            'provider' => Product::PROVIDER_DIGIFLAZZ,
            'provider_ref' => 'TRX-ADMIN-SYNC',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $order))
            ->assertOk()
            ->assertSee('Cek Status Digiflazz');

        $this->actingAs($admin)
            ->post(route('admin.transactions.sync-digiflazz', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        Queue::assertPushed(
            SyncDigiflazzTransactionStatus::class,
            fn (SyncDigiflazzTransactionStatus $job): bool => $job->orderId === $order->getKey(),
        );
        $this->assertDatabaseHas('transaction_logs', [
            'order_id' => $order->getKey(),
            'event' => 'digiflazz.status_sync_requested',
        ]);
    }

    public function test_only_processing_digiflazz_orders_can_be_synced(): void
    {
        Queue::fake();

        $admin = $this->admin(['dashboard.view', 'transactions.view', 'transactions.update']);
        $order = Order::factory()->create([
            'status' => OrderStatus::Success,
            'provider' => Product::PROVIDER_DIGIFLAZZ,
            'provider_ref' => 'TRX-FINAL-SYNC',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.transactions.show', $order))
            ->post(route('admin.transactions.sync-digiflazz', $order))
            ->assertRedirect(route('admin.transactions.show', $order))
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
    }

    public function test_a_user_without_update_permission_cannot_request_a_digiflazz_status_check(): void
    {
        $admin = $this->admin(['dashboard.view', 'transactions.view']);
        $order = Order::factory()->create([
            'status' => OrderStatus::Processing,
            'provider' => Product::PROVIDER_DIGIFLAZZ,
            'provider_ref' => 'TRX-FORBIDDEN-SYNC',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.transactions.sync-digiflazz', $order))
            ->assertForbidden();
    }

    public function test_transaction_detail_shows_wallet_movements_for_the_order(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['balance' => 10_000]);
        $order = Order::factory()->create(['user_id' => $customer->getKey()]);

        WalletLedger::debit($customer, 1_000, WalletTransactionType::Purchase, $order, 'Pembelian order', [], PinVerificationResult::valid($customer));

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $order))
            ->assertOk()
            ->assertSee('Mutasi Saldo Pelanggan')
            ->assertSee('Saldo sebelum')
            ->assertSee('Saldo sesudah')
            ->assertSee('Rp 10.000')
            ->assertSee('Rp 9.000')
            ->assertSee('Pembelian order');
    }

    public function test_transaction_detail_flags_conflicting_late_provider_statuses(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create([
            'status' => OrderStatus::Failed,
            'provider' => Product::PROVIDER_DIGIFLAZZ,
        ]);

        TransactionLog::factory()->create([
            'order_id' => $order->getKey(),
            'level' => TransactionLogLevel::Warning,
            'event' => 'digiflazz.late_result_ignored',
            'message' => 'Respons Digiflazz diabaikan.',
            'context' => [
                'local_status' => OrderStatus::Failed->value,
                'incoming_status' => 'Sukses',
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $order))
            ->assertOk()
            ->assertSee('Status provider perlu direkonsiliasi')
            ->assertSee('Status lokal transaksi sudah final')
            ->assertSee('Sukses')
            ->assertSee('Periksa Ref ID di dashboard Digiflazz');
    }

    public function test_transaction_detail_shows_midtrans_refund_amount_in_timeline(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::Qris,
            'metadata' => ['midtrans' => ['status' => 'refund', 'refund_amount' => '10000']],
        ]);

        TransactionLog::factory()->create([
            'order_id' => $order->getKey(),
            'level' => TransactionLogLevel::Info,
            'event' => 'midtrans.payment_status',
            'message' => 'Status pembayaran dari Midtrans diterima.',
            'context' => ['status' => 'refund', 'refund_amount' => '10000'],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $order))
            ->assertOk()
            ->assertSee('Nominal refund: Rp 10.000');
    }

    public function test_balance_checkout_records_transaction_logs(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000]);
        $product = $this->product();

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '123456789',
                'payment_method' => PaymentMethod::Saldo->value,
                'pin' => UserFactory::DEFAULT_PIN,
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
                'pin' => UserFactory::DEFAULT_PIN,
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

    public function test_admin_can_mark_a_pending_order_as_paid(): void
    {
        $admin = $this->admin(['dashboard.view', 'transactions.view', 'transactions.update']);
        $order = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::VirtualAccount,
            'payment_channel' => 'bca',
            'paid_at' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.transactions.settle', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(OrderStatus::Success, $order->status);
        $this->assertNotNull($order->paid_at);

        $this->assertDatabaseHas('transaction_logs', [
            'order_id' => $order->getKey(),
            'event' => 'order.paid',
        ]);
    }

    public function test_admin_cannot_settle_an_order_that_is_not_pending(): void
    {
        $admin = $this->admin(['dashboard.view', 'transactions.view', 'transactions.update']);
        $order = Order::factory()->create([
            'status' => OrderStatus::Success,
            'payment_method' => PaymentMethod::VirtualAccount,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.transactions.settle', $order))
            ->assertSessionHas('error');

        $this->assertSame(OrderStatus::Success, $order->fresh()->status);
    }

    public function test_admin_can_cancel_a_pending_order_and_stock_is_returned(): void
    {
        $admin = $this->admin(['dashboard.view', 'transactions.view', 'transactions.update']);
        $product = $this->product(['unlimited_stock' => false, 'stock' => 0, 'sold_count' => 1]);
        $order = Order::factory()->create([
            'product_id' => $product->getKey(),
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::VirtualAccount,
            'paid_at' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.transactions.expire', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(OrderStatus::Expired, $order->status);
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame(0, $product->fresh()->sold_count);
    }

    public function test_settle_and_expire_require_the_update_permission(): void
    {
        $admin = $this->admin(['dashboard.view', 'transactions.view']);
        $order = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::VirtualAccount,
            'paid_at' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.transactions.settle', $order))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.transactions.expire', $order))
            ->assertForbidden();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }
}
