<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ResellerKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: string} user & plain API key
     */
    private function activeReseller(array $attributes = []): array
    {
        $key = ResellerKey::generate();

        $user = User::factory()->create(array_merge([
            'is_reseller' => true,
            'reseller_api_key' => $key['hash'],
            'reseller_api_key_hint' => $key['hint'],
            'reseller_since' => now(),
        ], $attributes));

        return [$user, $key['plain']];
    }

    public function test_guest_is_redirected_to_login_from_the_reseller_panel(): void
    {
        $this->get(route('settings.reseller'))->assertRedirect(route('login'));
    }

    public function test_user_sees_the_reseller_panel_and_benefits_before_activation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.reseller'))
            ->assertOk()
            ->assertSee('Menjadi Reseller')
            ->assertSee('Aktifkan Menjadi Reseller')
            ->assertSee('Aturan penting sebelum aktivasi')
            ->assertSee('Pesanan melalui API dibayar dari saldo akun menggunakan harga reseller.')
            ->assertSee('Menonaktifkan reseller mencabut API key dan menghentikan akses API reseller.')
            ->assertSee('Akses API & API Key', false)
            ->assertSee(route('settings.reseller.docs'));
    }

    public function test_user_can_activate_and_receives_the_api_key_once(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.reseller.activate'))
            ->assertRedirect();

        $user->refresh();

        $this->assertTrue($user->is_reseller);
        $this->assertNotNull($user->reseller_api_key);
        $this->assertNotNull($user->reseller_since);
        $this->assertStringStartsWith('ap_', (string) $user->reseller_api_key_hint);

        $plain = session('api_key');
        $this->assertIsString($plain);
        $this->assertStringStartsWith('ap_', $plain);
        $this->assertSame(ResellerKey::hash($plain), $user->reseller_api_key);
    }

    public function test_activating_twice_keeps_the_existing_api_key(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.reseller.activate'));
        $firstHash = $user->fresh()->reseller_api_key;

        $this->actingAs($user->fresh())
            ->post(route('settings.reseller.activate'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertNull(session('api_key'));
        $this->assertSame($firstHash, $user->fresh()->reseller_api_key);
    }

    public function test_user_can_regenerate_the_api_key_and_the_old_one_is_invalid(): void
    {
        [$user, $oldPlain] = $this->activeReseller();

        $this->actingAs($user)
            ->post(route('settings.reseller.regenerate'))
            ->assertRedirect();

        $user->refresh();
        $newPlain = (string) session('api_key');

        $this->assertNotSame($oldPlain, $newPlain);
        $this->assertStringStartsWith('ap_', $newPlain);
        $this->assertSame(ResellerKey::hash($newPlain), $user->reseller_api_key);
        $this->assertDatabaseMissing('users', ['id' => $user->getKey(), 'reseller_api_key' => ResellerKey::hash($oldPlain)]);
    }

    public function test_regenerating_requires_an_active_reseller(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.reseller.regenerate'))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($user->fresh()->reseller_api_key);
    }

    public function test_user_can_deactivate_and_the_api_key_is_revoked(): void
    {
        [$user] = $this->activeReseller();

        $this->actingAs($user)
            ->post(route('settings.reseller.deactivate'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertFalse($user->is_reseller);
        $this->assertNull($user->reseller_api_key);
        $this->assertNull($user->reseller_api_key_hint);
        $this->assertNotNull($user->reseller_since);
    }

    public function test_deactivating_requires_an_active_reseller(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.reseller.deactivate'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_settings_index_shows_the_reseller_card(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee(route('settings.reseller'), false);
    }

    public function test_reseller_docs_page_shows_endpoints_and_base_url(): void
    {
        [$user] = $this->activeReseller();

        $this->actingAs($user)
            ->get(route('settings.reseller.docs'))
            ->assertOk()
            ->assertSee('Dokumentasi API Reseller')
            ->assertSee(url('/api/v1/reseller/products'), false)
            ->assertSee('X-API-Key')
            ->assertSee('reseller_price');
    }
}
