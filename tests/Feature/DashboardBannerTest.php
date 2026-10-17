<?php

namespace Tests\Feature;

use App\Models\DashboardBanner;
use App\Models\LoginBanner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardBannerTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_dashboard_shows_active_banners_only(): void
    {
        DashboardBanner::factory()->create(['title' => 'Banner Aktif Spesial', 'is_active' => true]);
        DashboardBanner::factory()->inactive()->create(['title' => 'Banner Nonaktif Rahasia']);

        $this->actingAs($this->user())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Banner Aktif Spesial')
            ->assertDontSee('Banner Nonaktif Rahasia');
    }

    public function test_dashboard_banner_with_image_renders_full_bleed_image_with_cta(): void
    {
        Storage::fake('banners');
        Storage::disk('banners')->put('promo.png', 'img');

        DashboardBanner::factory()->create([
            'title' => 'Judul Tersembunyi',
            'image' => 'promo.png',
            'link' => 'https://example.com/promo',
            'cta_label' => 'Klaim Sekarang',
            'is_active' => true,
        ]);

        $this->actingAs($this->user())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('promo.png')
            ->assertSee('promo-img-el')
            ->assertSee('Klaim Sekarang')
            ->assertDontSee('>Judul Tersembunyi<');
    }

    public function test_login_banner_popup_renders_image_when_flashed(): void
    {
        Storage::fake('banners');
        Storage::disk('banners')->put('login.png', 'img');

        LoginBanner::factory()->create(['title' => 'Pengumuman Penting', 'image' => 'login.png']);

        $this->actingAs($this->user())
            ->withSession(['show_login_banner' => true])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('login.png')
            ->assertSee('ap-banner-pop-img');
    }

    public function test_login_banner_modal_renders_when_flashed(): void
    {
        LoginBanner::factory()->create(['title' => 'Pengumuman Penting']);

        $this->actingAs($this->user())
            ->withSession(['show_login_banner' => true])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pengumuman Penting');
    }

    public function test_active_login_banner_is_flashed_after_user_login(): void
    {
        LoginBanner::factory()->create(['title' => 'Selamat Datang Bonus', 'is_active' => true]);
        $user = User::factory()->create(['password' => Hash::make('secret123')]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('show_login_banner', true);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Selamat Datang Bonus');
    }

    public function test_admin_login_does_not_flash_login_banner(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('dashboard.view', 'web');

        LoginBanner::factory()->create(['is_active' => true]);

        $admin = User::factory()->create([
            'is_admin' => true,
            'password' => Hash::make('secret123'),
        ]);
        $admin->givePermissionTo('dashboard.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret123'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionMissing('show_login_banner');
    }
}
