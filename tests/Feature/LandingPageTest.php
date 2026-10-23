<?php

namespace Tests\Feature;

use App\Models\LandingFaq;
use App\Models\LandingService;
use App\Models\LandingSetting;
use App\Models\LandingTestimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions): User
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

    public function test_guest_sees_active_landing_content(): void
    {
        LandingSetting::query()->create(array_merge(LandingSetting::defaults(), ['hero_title' => 'Judul Kustom']));
        LandingService::query()->create(['icon' => 'bolt', 'title' => 'Layanan Aktif', 'description' => 'Deskripsi', 'sort_order' => 0, 'is_active' => true]);
        LandingService::query()->create(['icon' => 'bolt', 'title' => 'Layanan Nonaktif', 'description' => 'Deskripsi', 'sort_order' => 1, 'is_active' => false]);
        LandingTestimonial::query()->create(['name' => 'Budi', 'role' => 'Reseller', 'message' => 'Mantap', 'rating' => 5, 'sort_order' => 0, 'is_active' => true]);
        LandingFaq::query()->create(['question' => 'Pertanyaan umum?', 'answer' => 'Jawaban umum.', 'sort_order' => 0, 'is_active' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSeeText('Judul Kustom')
            ->assertSeeText('Layanan Aktif')
            ->assertDontSeeText('Layanan Nonaktif')
            ->assertSeeText('Budi')
            ->assertSeeText('Pertanyaan umum?');
    }

    public function test_logged_in_user_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_admin_without_permission_cannot_open_landing_cms(): void
    {
        $admin = $this->admin(['dashboard.view']);

        $this->actingAs($admin)->get(route('admin.landing.settings'))->assertForbidden();
    }

    public function test_admin_can_update_landing_settings(): void
    {
        $admin = $this->admin(['dashboard.view', 'landing.view', 'landing.update']);

        $this->actingAs($admin)
            ->put(route('admin.landing.settings.update'), [
                'hero_title' => 'Judul Baru',
                'hero_subtitle' => 'Subjudul baru',
                'whatsapp_number' => '0812 3456 7890',
            ])
            ->assertRedirect();

        $this->assertSame('Judul Baru', LandingSetting::current()->hero_title);
    }

    public function test_admin_can_manage_services(): void
    {
        $admin = $this->admin(['dashboard.view', 'landing.view', 'landing.update']);

        $this->actingAs($admin)->post(route('admin.landing.services.store'), [
            'icon' => 'bolt',
            'title' => 'Layanan Baru',
            'description' => 'Deskripsi layanan',
            'sort_order' => 2,
            'is_active' => '1',
        ])->assertRedirect();

        $service = LandingService::query()->where('title', 'Layanan Baru')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.landing.services.update', $service), [
            'icon' => 'bolt',
            'title' => 'Layanan Diubah',
            'description' => 'Deskripsi baru',
            'sort_order' => 1,
        ])->assertRedirect();

        $this->assertSame('Layanan Diubah', $service->fresh()->title);
        $this->assertFalse($service->fresh()->is_active);

        $this->actingAs($admin)->delete(route('admin.landing.services.destroy', $service))->assertRedirect();
        $this->assertDatabaseMissing('landing_services', ['id' => $service->getKey()]);
    }
}
