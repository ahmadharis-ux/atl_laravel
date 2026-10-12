<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DigiflazzBalanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create(['is_admin' => true]);

        Permission::findOrCreate('dashboard.view', 'web');
        $user->givePermissionTo('dashboard.view');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_dashboard_shows_the_digiflazz_balance(): void
    {
        Http::fake([
            'api.digiflazz.com/*' => Http::response(['data' => ['deposit' => 250000]]),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Saldo Digiflazz')
            ->assertSee('250.000');
    }

    public function test_dashboard_warns_when_the_balance_is_low(): void
    {
        config()->set('services.digiflazz.low_balance_threshold', 100000);

        Http::fake([
            'api.digiflazz.com/*' => Http::response(['data' => ['deposit' => 50000]]),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Saldo Digiflazz menipis');
    }

    public function test_dashboard_stays_quiet_when_the_balance_is_healthy(): void
    {
        config()->set('services.digiflazz.low_balance_threshold', 100000);

        Http::fake([
            'api.digiflazz.com/*' => Http::response(['data' => ['deposit' => 500000]]),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Saldo Digiflazz menipis');
    }
}
