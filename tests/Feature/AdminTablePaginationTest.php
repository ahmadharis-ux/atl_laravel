<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\MasterBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminTablePaginationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $permissions
     */
    private function admin(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create(['is_admin' => true]);
        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function requestTable(string $route, User $user, array $params = [])
    {
        return $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route($route, $params));
    }

    public function test_users_table_returns_json_rows_and_footer_for_ajax(): void
    {
        $admin = $this->admin(['dashboard.view', 'users.view']);
        User::factory()->create(['name' => 'Budi Santoso']);

        $response = $this->requestTable('admin.users.index', $admin);

        $response->assertOk()
            ->assertJsonStructure(['rows', 'footer'])
            ->assertJsonPath('footer', fn ($footer) => str_contains($footer, 'pengguna'));

        $this->assertStringContainsString('Budi Santoso', $response->json('rows'));
    }

    public function test_users_table_filters_by_search_term(): void
    {
        $admin = $this->admin(['dashboard.view', 'users.view']);
        User::factory()->create(['name' => 'Cari Saya']);
        User::factory()->create(['name' => 'Abai Saja']);

        $response = $this->requestTable('admin.users.index', $admin, ['q' => 'Cari Saya']);

        $this->assertStringContainsString('Cari Saya', $response->json('rows'));
        $this->assertStringNotContainsString('Abai Saja', $response->json('rows'));
    }

    public function test_roles_table_returns_json_payload_for_ajax(): void
    {
        $admin = $this->admin(['dashboard.view', 'roles.view']);

        $this->requestTable('admin.roles.index', $admin)
            ->assertOk()
            ->assertJsonStructure(['rows', 'footer']);
    }

    public function test_users_and_roles_pages_render_the_full_view(): void
    {
        $admin = $this->admin(['dashboard.view', 'users.view', 'roles.view']);
        User::factory()->create(['name' => 'Rendered User']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Rendered User');

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Daftar Peran');
    }

    public function test_master_bank_table_includes_modals_for_ajax(): void
    {
        $admin = $this->admin(['dashboard.view', 'master-banks.view', 'master-banks.update']);
        $bank = MasterBank::factory()->create(['bank_name' => 'BCA']);

        $response = $this->requestTable('admin.master-banks.index', $admin);

        $response->assertOk()->assertJsonStructure(['rows', 'footer', 'modals']);
        $this->assertStringContainsString('modalBankEdit'.$bank->getKey(), $response->json('modals'));
    }

    public function test_brand_table_includes_modals_for_ajax(): void
    {
        $admin = $this->admin(['dashboard.view', 'master-brands.view', 'master-brands.update']);
        $brand = Brand::factory()->create(['name' => 'Telkomsel']);

        $response = $this->requestTable('admin.master-brands.index', $admin);

        $response->assertOk()->assertJsonStructure(['rows', 'footer', 'modals']);
        $this->assertStringContainsString('modalBrandEdit'.$brand->getKey(), $response->json('modals'));
    }

    public function test_admin_tables_render_the_server_side_header_form(): void
    {
        $admin = $this->admin([
            'dashboard.view', 'users.view', 'roles.view',
            'master-banks.view', 'master-brands.view', 'products.view', 'transactions.view', 'topups.view',
        ]);
        MasterBank::factory()->create(['bank_name' => 'BCA']);
        Brand::factory()->create(['name' => 'Telkomsel']);

        foreach ([
            route('admin.users.index'),
            route('admin.roles.index'),
            route('admin.master-banks.index'),
            route('admin.master-brands.index'),
            route('admin.transactions.index'),
            route('admin.topup.index'),
            route('admin.products.prepay.index'),
        ] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('data-ajax-form', false)
                ->assertSee('name="q"', false);
        }
    }

    public function test_component_showcase_table_still_uses_client_side_search(): void
    {
        $admin = $this->admin(['dashboard.view']);

        $this->actingAs($admin)
            ->get(route('admin.components'))
            ->assertOk()
            ->assertSee('js-dt-search', false)
            ->assertDontSee('data-ajax-form', false);
    }

    public function test_admin_layout_renders_the_mobile_sidebar_drawer(): void
    {
        $admin = $this->admin(['dashboard.view']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-admin-sidebar', false)
            ->assertSee('js-sidebar-mobile-toggle', false)
            ->assertSee('data-admin-sidebar-close', false);
    }
}
