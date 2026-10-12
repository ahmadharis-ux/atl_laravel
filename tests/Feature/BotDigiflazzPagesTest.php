<?php

namespace Tests\Feature;

use App\Models\BotDigiflazzConfig;
use App\Models\BotDigiflazzSyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BotDigiflazzPagesTest extends TestCase
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

    private function config(): BotDigiflazzConfig
    {
        return BotDigiflazzConfig::create([
            'name' => 'Scraper Utama',
            'cookies_content' => 'session=abc',
            'csrf_token' => 'csrf-token',
            'is_active' => true,
            'scraper_config' => ['pattern_mode' => 'AND'],
        ]);
    }

    public function test_edit_page_renders_saved_scraper_config_as_json(): void
    {
        $config = $this->config();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.bot.digiflazz.edit', $config));

        $response->assertOk()
            ->assertSee('js-multi-select', false)
            ->assertSee('Filter Sinkronisasi', false);
    }

    public function test_create_page_renders_selection_picker(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.bot.digiflazz.create'));

        $response->assertOk()
            ->assertSee('js-multi-select', false)
            ->assertSee('Filter Sinkronisasi', false);
    }

    public function test_sync_index_page_renders(): void
    {
        $config = $this->config();

        $job = BotDigiflazzSyncLog::createJob('job-123', $config->id);
        $job->addLog('info', 'Memulai scraping');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.bot.digiflazz.sync.index'));

        $response->assertOk();
        $response->assertSee('job-123');
    }

    public function test_sync_show_page_renders(): void
    {
        $config = $this->config();

        $job = BotDigiflazzSyncLog::createJob('job-456', $config->id);
        $job->addLog('info', 'Memulai scraping');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.bot.digiflazz.sync.show', $job));

        $response->assertOk();
        $response->assertSee('job-456');
    }
}
