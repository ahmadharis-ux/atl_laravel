<?php

namespace Tests\Feature;

use App\Enums\MerchandiseRedemptionStatus;
use App\Models\Merchandise;
use App\Models\MerchandiseRedemption;
use App\Models\User;
use App\Support\MerchandiseRedeemer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminMerchandiseRedemptionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['dashboard.view', 'redemptions.view', 'redemptions.update']): User
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

    private function pendingRedemption(array $userAttributes = [], array $itemAttributes = []): MerchandiseRedemption
    {
        $user = User::factory()->create(array_merge(['points' => 150], $userAttributes));
        $item = Merchandise::factory()->create(array_merge(['points_cost' => 100, 'stock' => 5, 'is_active' => true], $itemAttributes));

        MerchandiseRedeemer::redeem($user, $item);

        return $user->merchandiseRedemptions()->firstOrFail();
    }

    public function test_regular_users_cannot_access_redemption_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.redemptions.index'))
            ->assertForbidden();
    }

    public function test_admin_without_update_permission_cannot_complete_redemption(): void
    {
        $admin = $this->admin(['dashboard.view', 'redemptions.view']);
        $redemption = $this->pendingRedemption();

        $this->actingAs($admin)
            ->post(route('admin.redemptions.complete', $redemption))
            ->assertForbidden();

        $this->assertSame(MerchandiseRedemptionStatus::Pending, $redemption->fresh()->status);
    }

    public function test_admin_can_complete_a_pending_redemption(): void
    {
        $admin = $this->admin();
        $redemption = $this->pendingRedemption();

        $this->actingAs($admin)
            ->post(route('admin.redemptions.complete', $redemption))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(MerchandiseRedemptionStatus::Completed, $redemption->fresh()->status);
        $this->assertSame(50, (int) $redemption->user->fresh()->points);
        $this->assertSame(4, $redemption->merchandise->fresh()->stock);
    }

    public function test_completing_a_non_pending_redemption_is_rejected(): void
    {
        $admin = $this->admin();
        $redemption = $this->pendingRedemption();
        $redemption->forceFill(['status' => MerchandiseRedemptionStatus::Completed])->save();

        $this->actingAs($admin)
            ->post(route('admin.redemptions.complete', $redemption))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(MerchandiseRedemptionStatus::Completed, $redemption->fresh()->status);
    }

    public function test_admin_can_cancel_a_pending_redemption_and_refund_points(): void
    {
        $admin = $this->admin();
        $redemption = $this->pendingRedemption(['points' => 150], ['points_cost' => 100, 'stock' => 5]);

        $this->assertSame(50, (int) $redemption->user->fresh()->points);
        $this->assertSame(4, $redemption->merchandise->fresh()->stock);

        $this->actingAs($admin)
            ->post(route('admin.redemptions.cancel', $redemption))
            ->assertRedirect()
            ->assertSessionHas('status');

        $redemption->refresh();
        $this->assertSame(MerchandiseRedemptionStatus::Cancelled, $redemption->status);
        $this->assertSame(150, (int) $redemption->user->fresh()->points);
        $this->assertSame(5, $redemption->merchandise->fresh()->stock);
    }

    public function test_cancelling_a_non_pending_redemption_is_rejected(): void
    {
        $admin = $this->admin();
        $redemption = $this->pendingRedemption();
        MerchandiseRedeemer::complete($redemption);

        $this->actingAs($admin)
            ->post(route('admin.redemptions.cancel', $redemption))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(MerchandiseRedemptionStatus::Completed, $redemption->fresh()->status);
    }

    public function test_admin_can_view_redemption_list(): void
    {
        $admin = $this->admin();
        $this->pendingRedemption();

        $this->actingAs($admin)
            ->get(route('admin.redemptions.index'))
            ->assertOk()
            ->assertSee('Penukaran Poin');
    }
}
