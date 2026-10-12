<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminRefundTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $permissions
     */
    private function admin(array $permissions = ['dashboard.view', 'transactions.view', 'transactions.update']): User
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

    public function test_refunding_a_failed_saldo_order_credits_the_balance_once(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['balance' => 90_000]);

        $order = Order::factory()->create([
            'user_id' => $customer->getKey(),
            'status' => OrderStatus::Failed,
            'payment_method' => PaymentMethod::Saldo,
            'amount' => 10_000,
            'refunded_at' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.transactions.refund', $order))
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(100_000, $customer->fresh()->balance);
        $this->assertNotNull($order->refunded_at);

        $this->actingAs($admin)
            ->post(route('admin.transactions.refund', $order))
            ->assertRedirect();

        $this->assertSame(100_000, $customer->fresh()->balance);
    }

    public function test_refunding_a_non_saldo_order_only_marks_it_refunded(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['balance' => 90_000]);

        $order = Order::factory()->create([
            'user_id' => $customer->getKey(),
            'status' => OrderStatus::Failed,
            'payment_method' => PaymentMethod::VirtualAccount,
            'amount' => 10_000,
            'refunded_at' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.transactions.refund', $order))
            ->assertRedirect();

        $this->assertSame(90_000, $customer->fresh()->balance);
        $this->assertNotNull($order->fresh()->refunded_at);
    }

    public function test_a_non_failed_order_cannot_be_refunded(): void
    {
        $admin = $this->admin();

        $order = Order::factory()->create([
            'status' => OrderStatus::Processing,
            'refunded_at' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.transactions.refund', $order))
            ->assertSessionHas('error');

        $this->assertNull($order->fresh()->refunded_at);
    }

    public function test_a_user_without_update_permission_cannot_refund(): void
    {
        $admin = $this->admin(['dashboard.view', 'transactions.view']);

        $order = Order::factory()->create(['status' => OrderStatus::Failed, 'refunded_at' => null]);

        $this->actingAs($admin)
            ->post(route('admin.transactions.refund', $order))
            ->assertForbidden();
    }

    public function test_the_show_page_renders_the_provider_card_and_refund_action(): void
    {
        $admin = $this->admin();

        $order = Order::factory()->create([
            'status' => OrderStatus::Failed,
            'provider' => Product::PROVIDER_DIGIFLAZZ,
            'provider_ref' => 'TRX-SHOW-1',
            'provider_payload' => ['status' => 'Gagal', 'message' => 'stok habis'],
            'refunded_at' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $order))
            ->assertOk()
            ->assertSee('Status Provider')
            ->assertSee('Proses Refund')
            ->assertSee('modalProviderPayload', false);
    }
}
