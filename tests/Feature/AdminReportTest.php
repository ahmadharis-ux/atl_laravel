<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['dashboard.view', 'reports.view']): User
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

    private function order(array $attributes = []): Order
    {
        return Order::factory()->create(array_merge(['created_at' => now()], $attributes));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.reports.overview'))->assertRedirect(route('login'));
    }

    public function test_admin_without_the_reports_permission_is_forbidden(): void
    {
        $admin = $this->admin(['dashboard.view']);

        $this->actingAs($admin)->get(route('admin.reports.overview'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.reports.products'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.reports.providers'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.reports.customers'))->assertForbidden();
    }

    public function test_admin_with_the_reports_permission_sees_all_report_pages(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.reports.overview'))->assertOk()->assertSeeText('Transaksi & Laba');
        $this->actingAs($admin)->get(route('admin.reports.products'))->assertOk()->assertSeeText('Produk paling laku');
        $this->actingAs($admin)->get(route('admin.reports.providers'))->assertOk()->assertSeeText('Provider yang sering gangguan');
        $this->actingAs($admin)->get(route('admin.reports.customers'))->assertOk()->assertSeeText('Pelanggan & CRM');
    }

    public function test_overview_shows_revenue_profit_and_success_rate(): void
    {
        $admin = $this->admin();

        $expensive = Product::factory()->create(['cost_price' => 6000]);
        $cheap = Product::factory()->create(['cost_price' => 2000]);

        $this->order(['product_id' => $expensive->id, 'status' => OrderStatus::Success, 'amount' => 10000]);
        $this->order(['product_id' => $cheap->id, 'status' => OrderStatus::Success, 'amount' => 5000]);
        $this->order(['status' => OrderStatus::Failed, 'amount' => 7000]);
        $this->order(['status' => OrderStatus::Pending, 'amount' => 1000]);

        $this->actingAs($admin)
            ->get(route('admin.reports.overview'))
            ->assertOk()
            ->assertSee('Rp 15.000')
            ->assertSee('Rp 7.000')
            ->assertSee('50,0%')
            ->assertSee('Pendapatan')
            ->assertSee('Tren Harian')
            ->assertSee('Jam Sibuk');
    }

    public function test_overview_respects_the_period_filter(): void
    {
        $admin = $this->admin();

        $this->order(['status' => OrderStatus::Success, 'amount' => 10000]);
        $this->order(['status' => OrderStatus::Success, 'amount' => 99999, 'created_at' => now()->subDays(60)]);

        $this->actingAs($admin)
            ->get(route('admin.reports.overview', ['from' => now()->subDays(7)->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Rp 10.000')
            ->assertDontSee('Rp 99.999');
    }

    public function test_product_report_shows_top_and_bottom_products(): void
    {
        $admin = $this->admin();

        $popular = Product::factory()->create(['name' => 'Populer Banget', 'status' => true]);
        $ok = Product::factory()->create(['name' => 'Lumayan', 'status' => true]);
        $dead = Product::factory()->create(['name' => 'Mati Suri', 'status' => true]);

        foreach (range(1, 3) as $ignored) {
            $this->order(['product_id' => $popular->id, 'status' => OrderStatus::Success, 'amount' => 5000]);
        }

        $this->order(['product_id' => $ok->id, 'status' => OrderStatus::Success, 'amount' => 8000]);
        $this->order(['product_id' => $dead->id, 'status' => OrderStatus::Failed, 'amount' => 8000]);

        $response = $this->actingAs($admin)->get(route('admin.reports.products'))->assertOk();

        $body = $response->getContent();
        $topPosition = strpos($body, 'Paling Laku');
        $popularPosition = strpos($body, 'Populer Banget');
        $worstSectionPosition = strpos($body, 'Paling Tidak Laku');

        $this->assertNotFalse($topPosition);
        $this->assertNotFalse($popularPosition);
        $this->assertNotFalse($worstSectionPosition);
        $this->assertGreaterThan($topPosition, $popularPosition);
        $this->assertGreaterThan($popularPosition, $worstSectionPosition);
        $this->assertGreaterThan($worstSectionPosition, strpos($body, 'Mati Suri'));
    }

    public function test_provider_report_ranks_failure_rates(): void
    {
        $admin = $this->admin();

        $this->order(['provider' => 'digiflazz', 'status' => OrderStatus::Success, 'amount' => 1000]);
        $this->order(['provider' => 'digiflazz', 'status' => OrderStatus::Success, 'amount' => 1000]);
        $this->order(['provider' => 'digiflazz', 'status' => OrderStatus::Failed, 'amount' => 1000]);
        $this->order(['provider' => 'telkom', 'status' => OrderStatus::Success, 'amount' => 1000]);

        $this->actingAs($admin)
            ->get(route('admin.reports.providers'))
            ->assertOk()
            ->assertSee('Provider Sering Gangguan')
            ->assertSee('Provider Selalu Aman')
            ->assertSee('digiflazz')
            ->assertSee('33,3%')
            ->assertSee('telkom');
    }

    public function test_customer_report_lists_activity_and_segments(): void
    {
        $admin = $this->admin();

        $active = User::factory()->create(['name' => 'Siti Aktif', 'email' => 'siti@example.test']);
        $churn = User::factory()->create(['name' => 'Budi Churn', 'email' => 'budi@example.test']);

        $this->order(['user_id' => $active->id, 'status' => OrderStatus::Success, 'amount' => 20000]);

        $this->order(['user_id' => $churn->id, 'status' => OrderStatus::Success, 'amount' => 15000, 'created_at' => now()->subDays(40)]);
        $this->order(['user_id' => $churn->id, 'status' => OrderStatus::Success, 'amount' => 15000, 'created_at' => now()->subDays(50)]);
        $this->order(['user_id' => $churn->id, 'status' => OrderStatus::Success, 'amount' => 15000, 'created_at' => now()->subDays(60)]);

        $this->actingAs($admin)
            ->get(route('admin.reports.customers'))
            ->assertOk()
            ->assertSee('Siti Aktif')
            ->assertSee('Budi Churn')
            ->assertSee('Rp 45.000')
            ->assertSee('Top Spender (Sepanjang Masa)')
            ->assertSee('Aktivitas Transaksi Pelanggan');

        $this->actingAs($admin)
            ->get(route('admin.reports.customers', ['segment' => 'Berisiko Churn']))
            ->assertOk()
            ->assertSee('Budi Churn')
            ->assertDontSee('Siti Aktif');

        $this->actingAs($admin)
            ->get(route('admin.reports.customers', ['q' => 'siti']))
            ->assertOk()
            ->assertSee('Siti Aktif')
            ->assertDontSee('Budi Churn');
    }

    public function test_overview_export_returns_a_valid_excel_file(): void
    {
        $admin = $this->admin();

        $this->order(['status' => OrderStatus::Success, 'amount' => 10000]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.export', ['section' => 'overview', 'format' => 'xlsx']))
            ->assertOk();

        $response->assertDownload();
        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    public function test_provider_export_returns_a_valid_pdf(): void
    {
        $admin = $this->admin();

        $this->order(['provider' => 'digiflazz', 'status' => OrderStatus::Failed, 'amount' => 1000]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.export', ['section' => 'providers', 'format' => 'pdf']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $pdf = $response->streamedContent();

        $this->assertStringStartsWith('%PDF-1.', $pdf);
        $this->assertStringContainsString('digiflazz', $pdf);
        $this->assertStringEndsWith("%%EOF\n", $pdf);
    }

    public function test_export_rejects_unknown_sections_and_formats(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.reports.export', ['section' => 'users', 'format' => 'xlsx']))
            ->assertNotFound();

        $invalid = $this->actingAs($admin)
            ->from(route('admin.reports.overview'))
            ->get(route('admin.reports.export', ['section' => 'overview', 'format' => 'csv']));

        $invalid->assertRedirect(route('admin.reports.overview'));
        $invalid->assertSessionHasErrors('format');
    }
}
