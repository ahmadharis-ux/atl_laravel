<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ResellerCommission;
use App\Models\ResellerPayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ResellerPayoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_without_payout_permission_is_forbidden(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.reseller-payouts.index'))
            ->assertForbidden();
    }

    public function test_admin_can_mark_a_pending_payout_as_paid(): void
    {
        $admin = $this->adminWithPermission('resellers.payouts.update');
        $payout = $this->payout();

        $this->actingAs($admin)
            ->post(route('admin.reseller-payouts.update', $payout), [
                'action' => 'paid',
                'admin_note' => 'Transfer telah dilakukan.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('paid', $payout->fresh()->status);
        $this->assertSame($admin->getKey(), $payout->fresh()->processed_by);
        $this->assertNotNull($payout->fresh()->processed_at);
    }

    public function test_admin_rejection_makes_the_payout_eligible_to_be_requested_again(): void
    {
        $admin = $this->adminWithPermission('resellers.payouts.update');
        $reseller = User::factory()->create([
            'is_reseller' => true,
            'reseller_since' => now(),
        ]);
        $order = Order::factory()->for($reseller)->create();
        ResellerCommission::query()->create([
            'user_id' => $reseller->getKey(),
            'order_id' => $order->getKey(),
            'amount' => 3_000,
        ]);
        $payout = $this->payout($reseller, ['amount' => 3_000]);

        $this->actingAs($admin)
            ->post(route('admin.reseller-payouts.update', $payout), [
                'action' => 'rejected',
                'admin_note' => 'Rekening perlu diperiksa.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('rejected', $payout->fresh()->status);

        $this->actingAs($reseller)
            ->get(route('settings.reseller.store'))
            ->assertOk()
            ->assertSee('Komisi QRIS tersedia')
            ->assertSee('Rp 3.000');
    }

    public function test_admin_cannot_process_a_payout_twice(): void
    {
        $admin = $this->adminWithPermission('resellers.payouts.update');
        $payout = $this->payout(attributes: ['status' => 'paid']);

        $this->actingAs($admin)
            ->from(route('admin.reseller-payouts.index'))
            ->post(route('admin.reseller-payouts.update', $payout), [
                'action' => 'rejected',
                'admin_note' => 'Ditolak.',
            ])
            ->assertRedirect(route('admin.reseller-payouts.index'))
            ->assertSessionHasErrors('payout');

        $this->assertSame('paid', $payout->fresh()->status);
    }

    private function adminWithPermission(string $permission): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('dashboard.view', 'web');
        Permission::findOrCreate($permission, 'web');
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->givePermissionTo(['dashboard.view', $permission]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function payout(?User $reseller = null, array $attributes = []): ResellerPayout
    {
        $reseller ??= User::factory()->create();

        return ResellerPayout::query()->create(array_merge([
            'user_id' => $reseller->getKey(),
            'amount' => 3_000,
            'status' => 'pending',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Nama Reseller',
        ], $attributes));
    }
}
