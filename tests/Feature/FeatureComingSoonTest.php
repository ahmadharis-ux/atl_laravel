<?php

namespace Tests\Feature;

use App\Models\FeatureSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureComingSoonTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['is_admin' => false]);
    }

    public function test_merchandise_page_shows_coming_soon_when_disabled(): void
    {
        FeatureSetting::current()->update(['merchandise_enabled' => false]);

        $this->actingAs($this->user())
            ->get(route('merchandise.index'))
            ->assertOk()
            ->assertSee('Merchandise segera hadir')
            ->assertSee('Kembali ke Beranda');
    }

    public function test_ticket_page_shows_coming_soon_when_disabled(): void
    {
        FeatureSetting::current()->update(['ticket_enabled' => false]);

        $this->actingAs($this->user())
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Bantuan & Tiket segera hadir');
    }

    public function test_reseller_page_shows_coming_soon_when_disabled(): void
    {
        FeatureSetting::current()->update(['reseller_enabled' => false]);

        $this->actingAs($this->user())
            ->get(route('settings.reseller'))
            ->assertOk()
            ->assertSee('Reseller segera hadir');
    }

    public function test_enabled_features_render_normal_pages(): void
    {
        FeatureSetting::current()->update([
            'merchandise_enabled' => true,
            'reseller_enabled' => true,
        ]);

        $user = $this->user();

        $this->actingAs($user)
            ->get(route('merchandise.index'))
            ->assertOk()
            ->assertSee('Tukar poin reward')
            ->assertDontSee('Merchandise segera hadir');

        $this->actingAs($user)
            ->get(route('settings.reseller'))
            ->assertOk()
            ->assertDontSee('Reseller segera hadir');
    }

    public function test_ajax_requests_to_disabled_features_receive_json_503(): void
    {
        FeatureSetting::current()->update(['merchandise_enabled' => false]);

        $this->actingAs($this->user())
            ->getJson(route('merchandise.index'))
            ->assertStatus(503)
            ->assertJson(['message' => 'Fitur ini sedang dalam pengembangan.']);
    }
}
