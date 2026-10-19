<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Rbac;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed minimal untuk lingkungan UAT: hanya peran/permission dan satu akun
 * superadmin. Tidak membuat user contoh maupun produk.
 */
class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Rbac::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (Roles::all() as $role) {
            Role::findOrCreate($role, 'web')
                ->syncPermissions(Rbac::defaults()[$role] ?? []);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = User::query()->updateOrCreate(
            ['email' => 'superadmin@atlantic.test'],
            [
                'name' => 'Super Admin',
                'phone' => '081200000001',
                'password' => 'super12345',
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles([Roles::SUPERADMIN]);

        $this->command->info('Superadmin: superadmin@atlantic.test / super12345');
    }
}
