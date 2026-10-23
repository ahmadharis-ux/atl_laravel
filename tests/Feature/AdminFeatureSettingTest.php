<?php

namespace Tests\Feature;

use App\Models\FeatureSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminFeatureSettingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['dashboard.view', 'features.view', 'features.update']): User
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

    public function test_regular_users_cannot_access_feature_settings(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.features.index'))
            ->assertForbidden();
    }

    public function test_admin_without_update_permission_cannot_change_feature_settings(): void
    {
        $admin = $this->admin(['dashboard.view', 'features.view']);

        $this->actingAs($admin)
            ->put(route('admin.features.update'), [
                'reseller_enabled' => '1',
                'ticket_enabled' => '1',
                'merchandise_enabled' => '1',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_view_feature_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.features.index'))
            ->assertOk()
            ->assertSee('Manajemen Halaman')
            ->assertSee('Reseller')
            ->assertSee('Ticket')
            ->assertSee('Merchandise');
    }

    public function test_admin_can_update_feature_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.features.update'), [
                'reseller_enabled' => '0',
                'ticket_enabled' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Pengaturan halaman berhasil diperbarui.');

        $setting = FeatureSetting::current();

        $this->assertFalse($setting->reseller_enabled);
        $this->assertTrue($setting->ticket_enabled);
        $this->assertFalse($setting->merchandise_enabled);

        $this->assertFalse(FeatureSetting::enabled('reseller'));
        $this->assertTrue(FeatureSetting::enabled('ticket'));
        $this->assertFalse(FeatureSetting::enabled('merchandise'));
    }

    public function test_unknown_features_default_to_enabled(): void
    {
        $this->assertTrue(FeatureSetting::enabled('unknown-feature'));
    }
}
