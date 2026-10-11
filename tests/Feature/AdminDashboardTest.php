<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create(['is_admin' => true]);

        foreach (['dashboard.view', 'transactions.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo(['dashboard.view', 'transactions.view']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_regular_users_cannot_access_the_admin_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_dashboard_shows_operational_summary(): void
    {
        $admin = $this->admin();

        $category = Category::factory()->create(['slug' => 'games']);
        $product = Product::factory()->create([
            'name' => 'Mobile Legends 86 Diamonds',
            'category_id' => $category->id,
            'status' => true,
            'sold_count' => 42,
        ]);

        Order::factory()->create([
            'order_number' => 'TRX-20261010-DASH01',
            'product_id' => $product->getKey(),
            'status' => OrderStatus::Success,
            'amount' => 25_000,
            'created_at' => now(),
        ]);

        TransactionLog::factory()->error()->create([
            'message' => 'Provider timeout pada dashboard.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Transaksi Terbaru')
            ->assertSee('TRX-20261010-DASH01')
            ->assertSee('Produk Terlaris')
            ->assertSee('Mobile Legends 86 Diamonds')
            ->assertSee('Provider timeout pada dashboard.');
    }
}
