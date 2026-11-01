<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ResellerKey;
use App\Support\ResellerWebhook;
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
            'reseller_status' => 'active',
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
            ->assertSee('Kirim Permohonan Reseller')
            ->assertSee('Aturan penting sebelum aktivasi')
            ->assertSee('Pesanan melalui API dibayar dari saldo akun menggunakan harga reseller.')
            ->assertSee('Menonaktifkan reseller mencabut API key serta menutup akses API dan toko untuk pesanan baru.')
            ->assertSee('Akses API & API Key', false)
            ->assertSee(route('settings.reseller.docs'));
    }

    public function test_user_can_submit_an_application_and_must_wait_for_admin_approval(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.reseller.activate'), [
                'sales_channel' => 'api',
                'website' => 'https://reseller.example.test',
                'contact_channel' => 'whatsapp',
                'contact' => '08123456789',
                'accept_terms' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Permohonan reseller terkirim. Akses toko dan API akan aktif setelah ditinjau admin.');

        $user->refresh();

        $this->assertFalse($user->is_reseller);
        $this->assertSame('pending', $user->reseller_status);
        $this->assertSame('api', $user->reseller_sales_channel);
        $this->assertSame('1.0', $user->reseller_terms_version);
        $this->assertNotNull($user->reseller_terms_accepted_at);
        $this->assertNull($user->reseller_api_key);
        $this->assertNull($user->reseller_api_key_hint);
        $this->assertNull($user->reseller_since);
    }

    public function test_pending_application_shows_review_status_and_hides_activation_form(): void
    {
        $user = User::factory()->create([
            'reseller_status' => 'pending',
            'reseller_sales_channel' => 'api',
            'reseller_terms_accepted_at' => now(),
            'reseller_terms_version' => '1.0',
        ]);

        $this->actingAs($user)
            ->get(route('settings.reseller'))
            ->assertSee('Permohonan Sedang Ditinjau')
            ->assertSee('Toko dan API akan tersedia setelah admin menyetujui permohonan.')
            ->assertDontSee('Kirim Permohonan Reseller')
            ->assertDontSee('API Key');
    }

    public function test_rejected_application_shows_the_admin_reason_and_allows_resubmission(): void
    {
        $user = User::factory()->create([
            'reseller_status' => 'rejected',
            'reseller_status_reason' => 'Mohon lengkapi data website.',
        ]);

        $this->actingAs($user)
            ->get(route('settings.reseller'))
            ->assertSee('Permohonan Belum Disetujui')
            ->assertSee('Mohon lengkapi data website.')
            ->assertSee('Kirim Permohonan Reseller');
    }

    public function test_suspended_reseller_sees_the_reason_and_cannot_apply_again(): void
    {
        $user = User::factory()->create([
            'reseller_status' => 'suspended',
            'reseller_status_reason' => 'Verifikasi tambahan diperlukan.',
        ]);

        $this->actingAs($user)
            ->get(route('settings.reseller'))
            ->assertSee('Keanggotaan Ditangguhkan')
            ->assertSee('Verifikasi tambahan diperlukan.')
            ->assertDontSee('Kirim Permohonan Reseller');

        $this->actingAs($user)
            ->post(route('settings.reseller.activate'), [
                'sales_channel' => 'hosted',
                'contact_channel' => 'whatsapp',
                'contact' => '08123456789',
                'accept_terms' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_activation_requires_complete_details_and_explicit_terms_acceptance(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.reseller.activate'))
            ->assertSessionHasErrors(['sales_channel', 'contact_channel', 'contact', 'accept_terms']);

        $user->refresh();

        $this->assertFalse($user->is_reseller);
        $this->assertNull($user->reseller_api_key);
        $this->assertNull($user->reseller_terms_accepted_at);
    }

    public function test_submitting_twice_does_not_replace_a_pending_application(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.reseller.activate'), [
            'sales_channel' => 'hosted',
            'contact_channel' => 'whatsapp',
            'contact' => '08123456789',
            'accept_terms' => '1',
        ]);
        $submittedTermsAt = $user->fresh()->reseller_terms_accepted_at;

        $this->actingAs($user->fresh())
            ->post(route('settings.reseller.activate'), [
                'sales_channel' => 'api',
                'website' => 'https://changed.example.test',
                'contact_channel' => 'telegram',
                'contact' => '@changed',
                'accept_terms' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertNull(session('api_key'));
        $this->assertSame('hosted', $user->fresh()->reseller_sales_channel);
        $this->assertSame('1.0', $user->fresh()->reseller_terms_version);
        $this->assertSame($submittedTermsAt->toISOString(), $user->fresh()->reseller_terms_accepted_at->toISOString());
        $this->assertNull($user->fresh()->reseller_api_key);
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
        $this->assertSame(0, $user->notifications()->count());

        $this->actingAs($user)
            ->get(route('settings.reseller'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee($newPlain)
            ->assertSee('API key baru dibuat.')
            ->assertSee('role="status" aria-live="polite"', false);

        $this->actingAs($user)
            ->get(route('settings.reseller'))
            ->assertOk()
            ->assertDontSee($newPlain);
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
            ->assertSee('reseller_price')
            ->assertSee('Kontrak field produk')
            ->assertSee('"region": null', false)
            ->assertSee('"requires_zone": true', false)
            ->assertSee('The per page field must not be greater than 100.');
    }

    public function test_active_api_reseller_sees_contextual_api_onboarding_steps(): void
    {
        [$user] = $this->activeReseller(['reseller_sales_channel' => 'api']);

        $this->actingAs($user)
            ->get(route('settings.reseller'))
            ->assertSee('Langkah berikutnya')
            ->assertSee('Mulai dengan API')
            ->assertSee('API key sudah dibuat')
            ->assertSee('Webhook Status Pesanan')
            ->assertSee(route('settings.reseller.docs').'#doc-produk', false)
            ->assertSee(route('settings.reseller.docs').'#doc-pesanan', false)
            ->assertSee(route('deposits.create'), false)
            ->assertSee('id="api-key"', false)
            ->assertDontSee('Mulai dengan Toko Hosted');
    }

    public function test_active_reseller_can_save_a_public_webhook_url(): void
    {
        [$user] = $this->activeReseller();

        $this->actingAs($user)
            ->put(route('settings.reseller.webhook.update'), [
                'webhook_url' => 'https://callback.example.test/orders',
            ])
            ->assertRedirect();

        $this->assertSame('https://callback.example.test/orders', $user->fresh()->reseller_webhook_url);
    }

    public function test_production_webhook_urls_require_https_on_the_standard_port(): void
    {
        $originalEnvironment = $this->app->environment();

        try {
            $this->app->detectEnvironment(fn (): string => 'production');
            $this->assertTrue($this->app->environment('production'));
            $this->assertFalse(ResellerWebhook::isDeliverableUrl('https://callback.example.test:8443/orders'));
            [$user] = $this->activeReseller();

            $this->actingAs($user)
                ->withSession(['_token' => 'production-test-token'])
                ->put(route('settings.reseller.webhook.update'), [
                    '_token' => 'production-test-token',
                    'webhook_url' => 'https://callback.example.test:8443/orders',
                ])
                ->assertRedirect();

            $this->assertNull($user->fresh()->reseller_webhook_url);
        } finally {
            $this->app->detectEnvironment(fn (): string => $originalEnvironment);
        }
    }

    public function test_reseller_cannot_save_a_local_webhook_url(): void
    {
        [$user] = $this->activeReseller();

        $this->actingAs($user)
            ->put(route('settings.reseller.webhook.update'), [
                'webhook_url' => 'https://127.0.0.1/internal',
            ])
            ->assertSessionHasErrors('webhook_url');

        $this->assertNull($user->fresh()->reseller_webhook_url);
    }

    public function test_webhook_url_rejects_ambiguous_ip_and_trailing_dot_hosts(): void
    {
        $this->assertFalse(ResellerWebhook::isDeliverableUrl('https://127.1/internal'));
        $this->assertFalse(ResellerWebhook::isDeliverableUrl('https://callback.example.test./orders'));
    }

    public function test_active_reseller_can_generate_a_secret_displayed_only_once(): void
    {
        [$user] = $this->activeReseller();

        $this->actingAs($user)
            ->post(route('settings.reseller.webhook.secret'))
            ->assertRedirect();

        $secret = (string) session('webhook_secret');
        $user->refresh();

        $this->assertStringStartsWith('whsec_', $secret);
        $this->assertSame($secret, $user->reseller_webhook_secret);
        $this->assertNotSame($secret, $user->getRawOriginal('reseller_webhook_secret'));
        $this->assertArrayNotHasKey('reseller_webhook_secret', $user->toArray());
    }

    public function test_active_api_reseller_without_a_key_is_prompted_to_create_one(): void
    {
        $user = User::factory()->create([
            'is_reseller' => true,
            'reseller_sales_channel' => 'api',
            'reseller_since' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('settings.reseller'))
            ->assertSee('API key belum dibuat')
            ->assertSee('Buat API key')
            ->assertSee('href="#api-key"', false);
    }

    public function test_active_hosted_reseller_sees_store_and_commission_steps(): void
    {
        [$user] = $this->activeReseller(['reseller_sales_channel' => 'hosted']);

        $this->actingAs($user)
            ->get(route('settings.reseller'))
            ->assertSee('Mulai dengan Toko Hosted')
            ->assertSee(route('storefront.index', $user), false)
            ->assertSee(route('settings.reseller.store'), false)
            ->assertDontSee('Mulai dengan API');
    }
}
