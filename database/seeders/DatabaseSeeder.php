<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Rbac;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
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

        $accounts = [
            ['name' => 'Super Admin', 'email' => 'superadmin@atlantic.test', 'phone' => '081200000001', 'password' => 'super12345', 'role' => Roles::SUPERADMIN],
            ['name' => 'Admin Atlantic', 'email' => 'admin@atlantic.test', 'phone' => '081200000000', 'password' => 'admin123', 'role' => Roles::ADMIN],
            ['name' => 'Staff Atlantic', 'email' => 'staff@atlantic.test', 'phone' => '081200000002', 'password' => 'staff12345', 'role' => Roles::STAFF],
            ['name' => 'CS Atlantic', 'email' => 'cs@atlantic.test', 'phone' => '081200000003', 'password' => 'cs1234567', 'role' => Roles::CS],
            ['name' => 'User Demo', 'email' => 'user@atlantic.test', 'phone' => '081211111111', 'password' => 'user12345', 'role' => Roles::USER],
        ];

        foreach ($accounts as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'phone' => $account['phone'],
                    'password' => $account['password'],
                    'is_admin' => in_array($account['role'], Roles::adminArea(), true),
                ],
            );

            $user->syncRoles([$account['role']]);
        }

        if (User::count() <= count($accounts)) {
            User::factory(14)->create();
        }

        User::query()->whereDoesntHave('roles')->chunkById(500, function ($users): void {
            foreach ($users as $user) {
                $user->assignRole(Roles::USER);
            }
        });

        $this->call(ProductSeeder::class);
    }
}
