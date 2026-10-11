<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('dashboard.view', 'web');

        $user = User::factory()->create(['is_admin' => true]);
        $user->givePermissionTo('dashboard.view');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_admin_is_redirected_to_the_panel_from_user_pages(): void
    {
        $admin = $this->admin();

        foreach ([route('dashboard'), route('saldo.index'), route('orders.index')] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertRedirect(route('admin.dashboard'));
        }
    }

    public function test_admin_can_still_log_out_from_the_user_area(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_regular_user_sees_a_forbidden_page_on_admin_area(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden()
            ->assertSee('Akses ditolak')
            ->assertSee('403');
    }
}
