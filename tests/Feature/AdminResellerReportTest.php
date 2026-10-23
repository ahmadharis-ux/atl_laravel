<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\ResellerKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminResellerReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['dashboard.view', 'resellers.view']): User
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

    /**
     * @return array{0: User, 1: string} user & plain API key
     */
    private function reseller(array $attributes = []): array
    {
        $key = ResellerKey::generate();

        $user = User::factory()->create(array_merge([
            'is_reseller' => true,
            'reseller_api_key' => $key['hash'],
            'reseller_api_key_hint' => $key['hint'],
            'reseller_since' => now()->subDays(3),
        ], $attributes));

        return [$user, $key['plain']];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.resellers.index'))->assertRedirect(route('login'));
    }

    public function test_admin_without_the_resellers_permission_is_forbidden(): void
    {
        $admin = $this->admin(['dashboard.view']);

        $this->actingAs($admin)
            ->get(route('admin.resellers.index'))
            ->assertForbidden();
    }

    public function test_report_only_lists_users_who_opted_in_as_resellers(): void
    {
        $admin = $this->admin();
        $this->reseller(['email' => 'aktif@reseller.test']);
        $this->reseller(['email' => 'nonaktif@reseller.test', 'is_reseller' => false, 'reseller_api_key' => null, 'reseller_api_key_hint' => null]);
        User::factory()->create(['email' => 'biasa@user.test']);

        $this->actingAs($admin)
            ->get(route('admin.resellers.index'))
            ->assertOk()
            ->assertSee('Laporan Reseller')
            ->assertSee('aktif@reseller.test')
            ->assertSee('nonaktif@reseller.test')
            ->assertDontSee('biasa@user.test');
    }

    public function test_report_shows_statistics_cards(): void
    {
        $admin = $this->admin();
        $this->reseller();
        $this->reseller(['is_reseller' => false, 'reseller_api_key' => null, 'reseller_api_key_hint' => null]);

        $this->actingAs($admin)
            ->get(route('admin.resellers.index'))
            ->assertOk()
            ->assertSee('Total Reseller')
            ->assertSee('Reseller Aktif')
            ->assertSee('Transaksi via API')
            ->assertSee('Nominal via API');
    }

    public function test_report_can_be_searched_by_name_or_email(): void
    {
        $admin = $this->admin();
        $this->reseller(['name' => 'Budi Santoso', 'email' => 'budi@reseller.test']);
        $this->reseller(['name' => 'Siti Aminah', 'email' => 'siti@reseller.test']);

        $this->actingAs($admin)
            ->get(route('admin.resellers.index', ['q' => 'siti']))
            ->assertOk()
            ->assertSee('siti@reseller.test')
            ->assertDontSee('budi@reseller.test');
    }

    public function test_report_lists_api_order_activity_per_reseller(): void
    {
        $admin = $this->admin();
        [$reseller] = $this->reseller();
        $product = Product::factory()->create(['cost_price' => 10_000, 'markup_value' => 0]);

        Order::factory()->for($reseller)->create(['product_id' => $product->getKey(), 'source' => 'api', 'amount' => 9_500]);
        Order::factory()->for($reseller)->create(['product_id' => $product->getKey(), 'source' => 'web', 'amount' => 10_000]);

        $this->actingAs($admin)
            ->get(route('admin.resellers.index'))
            ->assertOk()
            ->assertSee('Rp 9.500');
    }

    public function test_report_returns_rendered_rows_for_ajax_requests(): void
    {
        $admin = $this->admin();
        [$reseller] = $this->reseller();

        $response = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('admin.resellers.index'))
            ->assertOk()
            ->assertJsonStructure(['rows', 'footer']);

        $this->assertStringContainsString($reseller->email, (string) $response->json('rows'));
        $this->assertStringContainsString('reseller', (string) $response->json('footer'));
    }
}
