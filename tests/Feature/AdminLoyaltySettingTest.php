<?php

namespace Tests\Feature;

use App\Enums\UserTier;
use App\Models\LoyaltySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLoyaltySettingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['dashboard.view', 'loyalty.view', 'loyalty.update']): User
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

    public function test_regular_users_cannot_access_loyalty_settings(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.loyalty-settings.index'))
            ->assertForbidden();
    }

    public function test_loyalty_settings_page_uses_the_image_badge_for_each_tier(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->get(route('admin.loyalty-settings.index'));

        foreach (['silver', 'gold', 'platinum'] as $tier) {
            $response
                ->assertSee(asset('assets/images/'.$tier.'.png'), false)
                ->assertSee('alt="'.ucfirst($tier).'"', false);
        }
    }

    public function test_admin_without_update_permission_cannot_change_thresholds(): void
    {
        $admin = $this->admin(['dashboard.view', 'loyalty.view']);

        $this->actingAs($admin)
            ->put(route('admin.loyalty-settings.update'), [
                'gold_points' => 50,
                'platinum_points' => 200,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_update_thresholds_and_tiers_follow_them(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.loyalty-settings.update'), [
                'gold_points' => 30,
                'platinum_points' => 80,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $setting = LoyaltySetting::current();
        $this->assertSame(30, $setting->gold_points);
        $this->assertSame(80, $setting->platinum_points);

        $this->assertSame(UserTier::Silver, UserTier::fromPoints(29));
        $this->assertSame(UserTier::Gold, UserTier::fromPoints(30));
        $this->assertSame(UserTier::Platinum, UserTier::fromPoints(80));
    }

    public function test_platinum_must_be_greater_than_gold(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.loyalty-settings.update'), [
                'gold_points' => 100,
                'platinum_points' => 50,
            ])
            ->assertSessionHasErrors('platinum_points');
    }

    public function test_negative_thresholds_are_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.loyalty-settings.update'), [
                'gold_points' => 0,
                'platinum_points' => 500,
            ])
            ->assertSessionHasErrors('gold_points');
    }

    public function test_tier_changes_are_reflected_on_user_pages(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['points' => 60]);

        $this->assertSame(UserTier::Silver, $user->tier());

        $this->actingAs($admin)
            ->put(route('admin.loyalty-settings.update'), [
                'gold_points' => 50,
                'platinum_points' => 100,
            ])
            ->assertRedirect();

        $this->assertSame(UserTier::Gold, $user->fresh()->tier());
    }
}
