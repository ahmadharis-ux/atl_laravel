<?php

namespace Tests\Feature;

use App\Models\Order;
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
