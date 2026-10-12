<?php

namespace Tests\Feature;

use App\Models\BotDigiflazzConfig;
use App\Models\BotDigiflazzProduct;
use App\Models\BotDigiflazzSeller;
use App\Models\BotDigiflazzSyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BotDigiflazzConfigDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = ['dashboard.view', 'products.view', 'products.update'];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create(['is_admin' => true]);
        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function configWithRelations(): BotDigiflazzConfig
    {
        $config = BotDigiflazzConfig::create([
            'name' => 'Scraper Utama',
            'cookies_content' => 'session=abc; XSRF-TOKEN=xyz',
            'csrf_token' => 'csrf-token',
            'socket_id' => '123.456',
            'xsrf_token' => 'xyz',
            'is_active' => true,
            'scraper_config' => [],
        ]);

        $product = BotDigiflazzProduct::create([
            'config_id' => $config->id,
            'digiflazz_product_id' => 'prod-1',
            'product_code' => 'ML-86',
            'product_name' => 'ML 86 Diamond',
        ]);

        BotDigiflazzSeller::create([
            'config_id' => $config->id,
            'product_id' => $product->id,
            'seller_name' => 'Seller A',
        ]);

        $job = BotDigiflazzSyncLog::createJob('job-uuid-1', $config->id);
        $job->addLog('info', 'Memulai scraping');

        return $config;
    }

    public function test_admin_can_delete_a_config_with_its_related_data(): void
    {
        $config = $this->configWithRelations();

        $this->actingAs($this->admin())
            ->delete(route('admin.bot.digiflazz.destroy', $config))
            ->assertRedirect(route('admin.bot.digiflazz.index'));

        $this->assertDatabaseCount('bot_digiflazz_configs', 0);
        $this->assertDatabaseCount('bot_digiflazz_products', 0);
        $this->assertDatabaseCount('bot_digiflazz_sellers', 0);
        $this->assertDatabaseCount('bot_digiflazz_sync_logs', 0);
    }

    public function test_delete_returns_json_for_ajax_requests(): void
    {
        $config = $this->configWithRelations();

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.bot.digiflazz.destroy', $config))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('bot_digiflazz_configs', 0);
    }

    public function test_delete_action_renders_a_method_spoofed_form(): void
    {
        $this->configWithRelations();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.bot.digiflazz.index'));

        $response->assertOk();
        $response->assertSee('data-submit-form', false);
        $response->assertSee('name="_method" value="DELETE"', false);
    }
}
