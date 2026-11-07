<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ResellerCommission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminUserDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_balance_points_role_and_transactions(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create([
            'name' => 'Detail User',
            'balance' => 125_000,
            'points' => 350,
        ]);
        $order = Order::factory()->for($user)->create(['source' => 'api']);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Detail User')
            ->assertSee('Tanpa peran')
            ->assertSee('Rp 125.000')
            ->assertSee('350')
            ->assertSee($order->order_number)
            ->assertDontSee('Riwayat Komisi Reseller');
    }

    public function test_admin_can_view_reseller_commissions_on_user_detail(): void
    {
        $admin = $this->admin();
        $reseller = User::factory()->create([
            'is_reseller' => true,
            'reseller_status' => 'active',
            'reseller_since' => now(),
        ]);
        $order = Order::factory()->for($reseller)->create(['source' => 'store_qris']);
        ResellerCommission::query()->create([
            'user_id' => $reseller->getKey(),
            'order_id' => $order->getKey(),
            'amount' => 1_200,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $reseller))
            ->assertOk()
            ->assertSee('Reseller')
            ->assertSee('Riwayat Komisi Reseller')
            ->assertSee('+Rp 1.200')
            ->assertSee($order->order_number);
    }

    private function admin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('dashboard.view', 'web');
        Permission::findOrCreate('users.view', 'web');

        $admin = User::factory()->create(['is_admin' => true]);
        $admin->givePermissionTo(['dashboard.view', 'users.view']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }
}
