<?php

namespace Tests\Feature;

use App\Models\LandingFaq;
use App\Models\LandingService;
use App\Models\LandingSetting;
use App\Models\LandingTestimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    private function travelPastComingSoon(): void
    {
        config(['landing.coming_soon_until' => '2026-11-06 00:00:00']);
        $this->travelTo(Carbon::parse('2026-11-06 00:00:00', 'Asia/Jakarta'));
    }

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
        config(['landing.coming_soon_until' => '2026-11-06 00:00:00']);
        $this->travelTo(Carbon::parse('2026-11-06 00:00:00', 'Asia/Jakarta'));

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

    public function test_guest_sees_coming_soon_before_launch(): void
    {
        config(['landing.coming_soon_until' => '2026-11-06 00:00:00']);
        $this->travelTo(Carbon::parse('2026-11-05 23:59:59', 'Asia/Jakarta'));

        $this->get('/')
            ->assertStatus(503)
            ->assertViewIs('landing.coming-soon')
            ->assertSeeText('Segera Hadir')
            ->assertSeeText('06/11/2026 pukul 00.00 WIB');

        foreach (['/login', '/dashboard', '/admin'] as $path) {
            $this->get($path)
                ->assertStatus(503)
                ->assertSeeText('Segera Hadir');
        }

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin')
            ->assertStatus(503)
            ->assertSeeText('Segera Hadir');
    }

    public function test_allowed_ip_can_access_web_pages_during_coming_soon(): void
    {
        config([
            'landing.coming_soon_until' => '2026-11-06 00:00:00',
            'landing.coming_soon_allowed_ips' => ['178.83.180.23', '203.0.113.10'],
        ]);
        $this->travelTo(Carbon::parse('2026-11-05 23:59:59', 'Asia/Jakarta'));

        foreach (['178.83.180.23', '203.0.113.10'] as $allowedIp) {
            $this->withServerVariables(['REMOTE_ADDR' => $allowedIp])
                ->get('/login')
                ->assertOk();
        }
    }

    public function test_allowed_ip_can_access_through_cloudflare(): void
    {
        config([
            'landing.coming_soon_until' => '2026-11-06 00:00:00',
            'landing.coming_soon_allowed_ips' => ['103.147.8.176'],
        ]);
        $this->travelTo(Carbon::parse('2026-11-05 23:59:59', 'Asia/Jakarta'));

        $this->withServerVariables(['REMOTE_ADDR' => '172.71.152.53'])
            ->withHeader('CF-Connecting-IP', '103.147.8.176')
            ->get('/login')
            ->assertOk();
    }

    public function test_non_allowed_ip_is_blocked_through_cloudflare(): void
    {
        config([
            'landing.coming_soon_until' => '2026-11-06 00:00:00',
            'landing.coming_soon_allowed_ips' => ['103.147.8.176'],
        ]);
        $this->travelTo(Carbon::parse('2026-11-05 23:59:59', 'Asia/Jakarta'));

        $this->withServerVariables(['REMOTE_ADDR' => '172.71.152.53'])
            ->withHeader('CF-Connecting-IP', '198.51.100.20')
            ->get('/login')
            ->assertStatus(503)
            ->assertSeeText('Segera Hadir');
    }

    public function test_cloudflare_connection_without_valid_client_ip_is_blocked(): void
    {
        config([
            'landing.coming_soon_until' => '2026-11-06 00:00:00',
            'landing.coming_soon_allowed_ips' => ['103.147.8.176'],
        ]);
        $this->travelTo(Carbon::parse('2026-11-05 23:59:59', 'Asia/Jakarta'));

        $this->withServerVariables(['REMOTE_ADDR' => '172.71.152.53'])
            ->withHeader('CF-Connecting-IP', 'not-an-ip')
            ->withHeader('X-Forwarded-For', '103.147.8.176')
            ->get('/login')
            ->assertStatus(503)
            ->assertSeeText('Segera Hadir');
    }

    public function test_untrusted_connection_cannot_spoof_cloudflare_client_ip(): void
    {
        config([
            'landing.coming_soon_until' => '2026-11-06 00:00:00',
            'landing.coming_soon_allowed_ips' => ['103.147.8.176'],
        ]);
        $this->travelTo(Carbon::parse('2026-11-05 23:59:59', 'Asia/Jakarta'));

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->withHeader('CF-Connecting-IP', '103.147.8.176')
            ->withHeader('X-Forwarded-For', '103.147.8.176')
            ->get('/login')
            ->assertStatus(503)
            ->assertSeeText('Segera Hadir');
    }

    public function test_forwarded_ip_header_cannot_bypass_coming_soon(): void
    {
        config([
            'landing.coming_soon_until' => '2026-11-06 00:00:00',
            'landing.coming_soon_allowed_ips' => ['178.83.180.23'],
        ]);
        $this->travelTo(Carbon::parse('2026-11-05 23:59:59', 'Asia/Jakarta'));

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->withHeader('X-Forwarded-For', '178.83.180.23')
            ->get('/login')
            ->assertStatus(503)
            ->assertSeeText('Segera Hadir');
    }

    public function test_logged_in_user_is_redirected_to_dashboard(): void
    {
        config(['landing.coming_soon_until' => '2026-11-06 00:00:00']);
        $this->travelTo(Carbon::parse('2026-11-06 00:00:00', 'Asia/Jakarta'));

        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_admin_without_permission_cannot_open_landing_cms(): void
    {
        $this->travelPastComingSoon();
        $admin = $this->admin(['dashboard.view']);

        $this->actingAs($admin)->get(route('admin.landing.settings'))->assertForbidden();
    }

    public function test_admin_can_update_landing_settings(): void
    {
        $this->travelPastComingSoon();
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
        $this->travelPastComingSoon();
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
