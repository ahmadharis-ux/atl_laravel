<?php

namespace Tests\Feature;

use App\Models\DashboardBanner;
use App\Models\LoginBanner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminBannerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $permissions
     */
    private function admin(array $permissions = [
        'dashboard.view',
        'login-banners.view',
        'login-banners.update',
        'dashboard-banners.view',
        'dashboard-banners.update',
    ]): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create(['is_admin' => true]);
        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_admin_can_manage_login_banners(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.login-banners.index'))
            ->assertOk();

        $this->actingAs($admin)->post(route('admin.login-banners.store'), [
            'title' => 'Promo Lebaran',
            'body' => 'Diskon spesial lebaran.',
            'is_active' => '1',
            'sort_order' => 1,
        ])->assertSessionHas('status');

        $banner = LoginBanner::query()->sole();
        $this->assertSame('Promo Lebaran', $banner->title);
        $this->assertTrue($banner->is_active);

        $this->actingAs($admin)->put(route('admin.login-banners.update', $banner), [
            'title' => 'Promo Lebaran Updated',
            'sort_order' => 3,
        ])->assertSessionHas('status');

        $banner->refresh();
        $this->assertSame('Promo Lebaran Updated', $banner->title);
        $this->assertFalse($banner->is_active);
        $this->assertSame(3, $banner->sort_order);

        $this->actingAs($admin)
            ->delete(route('admin.login-banners.destroy', $banner))
            ->assertSessionHas('status');

        $this->assertSame(0, LoginBanner::query()->count());
    }

    public function test_uploaded_banner_image_is_converted_to_webp(): void
    {
        Storage::fake('banners');

        $this->actingAs($this->admin())
            ->post(route('admin.login-banners.store'), [
                'title' => 'Promo WebP',
                'is_active' => '1',
                'image' => UploadedFile::fake()->image('promo.png', 2000, 1200),
            ])
            ->assertSessionHas('status');

        $banner = LoginBanner::query()->sole();

        $this->assertNotNull($banner->image);
        $this->assertStringEndsWith('.webp', $banner->image);
        Storage::disk('banners')->assertExists($banner->image);
    }

    public function test_admin_can_manage_dashboard_banners(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.dashboard-banners.store'), [
            'badge' => 'FLASH',
            'title' => 'Cashback 20%',
            'subtitle' => 'Hari ini saja',
            'is_active' => '1',
        ])->assertSessionHas('status');

        $banner = DashboardBanner::query()->sole();
        $this->assertSame('Cashback 20%', $banner->title);
        $this->assertTrue($banner->is_active);

        $this->actingAs($admin)
            ->delete(route('admin.dashboard-banners.destroy', $banner))
            ->assertSessionHas('status');

        $this->assertSame(0, DashboardBanner::query()->count());
    }

    public function test_banner_validation_rejects_ends_before_starts(): void
    {
        $this->actingAs($this->admin())->post(route('admin.login-banners.store'), [
            'title' => 'Banner',
            'starts_at' => '2026-12-10',
            'ends_at' => '2026-12-01',
        ])->assertSessionHasErrors('ends_at');

        $this->assertSame(0, LoginBanner::query()->count());
    }

    public function test_user_without_permission_cannot_view_banners(): void
    {
        $this->actingAs($this->admin(['dashboard.view']))
            ->get(route('admin.login-banners.index'))
            ->assertForbidden();

        $this->actingAs($this->admin(['dashboard.view']))
            ->get(route('admin.dashboard-banners.index'))
            ->assertForbidden();
    }

    public function test_admin_search_returns_grouped_results(): void
    {
        $admin = $this->admin([
            'dashboard.view',
            'users.view',
            'transactions.view',
            'products.view',
        ]);

        User::factory()->create(['name' => 'Cari Nama Unik']);

        $this->actingAs($admin)
            ->getJson(route('admin.search', ['q' => 'Cari Nama Unik']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Pengguna'])
            ->assertJsonFragment(['text' => 'Cari Nama Unik']);
    }

    public function test_admin_search_requires_minimum_characters(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('admin.search', ['q' => 'a']))
            ->assertOk()
            ->assertExactJson(['groups' => []]);
    }
}
