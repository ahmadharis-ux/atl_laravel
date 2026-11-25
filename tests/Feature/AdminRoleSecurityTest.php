<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Rbac;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminRoleSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Rbac::ensurePermissions();

        foreach (Roles::all() as $role) {
            Role::findOrCreate($role, 'web')->syncPermissions(Rbac::defaults()[$role] ?? []);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['is_admin' => $role !== Roles::USER]);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_cannot_edit_its_own_role(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $adminRole = Role::findByName(Roles::ADMIN, 'web');

        $this->actingAs($admin)
            ->put(route('admin.roles.update', $adminRole), ['permissions' => Rbac::all()])
            ->assertForbidden();

        $this->assertFalse($adminRole->fresh()->hasPermissionTo('roles.delete'));
    }

    public function test_admin_cannot_grant_permissions_it_does_not_hold(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $staffRole = Role::findByName(Roles::STAFF, 'web');

        $this->actingAs($admin)
            ->put(route('admin.roles.update', $staffRole), ['permissions' => ['dashboard.view', 'roles.delete']])
            ->assertSessionHasErrors('permissions.1');

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), ['name' => 'finance', 'permissions' => ['queue.update']])
            ->assertSessionHasErrors('permissions.0');

        $this->assertFalse($staffRole->fresh()->hasPermissionTo('roles.delete'));
        $this->assertNull(Role::query()->where('name', 'finance')->first());
    }

    public function test_customer_role_cannot_receive_admin_permissions(): void
    {
        $superadmin = $this->userWithRole(Roles::SUPERADMIN);
        $userRole = Role::findByName(Roles::USER, 'web');

        $this->actingAs($superadmin)
            ->put(route('admin.roles.update', $userRole), ['permissions' => ['dashboard.view']])
            ->assertSessionHasErrors('permissions');

        $this->assertCount(0, $userRole->fresh()->permissions);
    }

    public function test_admin_can_still_edit_a_lower_role_within_its_permissions(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $csRole = Role::findByName(Roles::CS, 'web');

        $this->actingAs($admin)
            ->put(route('admin.roles.update', $csRole), ['permissions' => ['dashboard.view', 'tickets.view']])
            ->assertRedirect(route('admin.roles.index'));

        $this->assertEqualsCanonicalizing(['dashboard.view', 'tickets.view'], $csRole->fresh()->permissions->pluck('name')->all());
    }

    public function test_only_superadmin_can_manage_users_with_a_custom_role(): void
    {
        $staff = $this->userWithRole(Roles::STAFF);
        Role::findOrCreate('finance', 'web');
        $target = User::factory()->create(['is_admin' => true]);
        $target->assignRole('finance');

        $this->assertFalse(Rbac::canManageUser($staff, $target));
        $this->assertTrue(Rbac::canManageUser($this->userWithRole(Roles::SUPERADMIN), $target));
    }
}
