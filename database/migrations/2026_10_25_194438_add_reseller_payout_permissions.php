<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $permissionNames = ['resellers.payouts.view', 'resellers.payouts.update'];

        DB::table($tableNames['permissions'])->insertOrIgnore([
            ['name' => 'resellers.payouts.view', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'resellers.payouts.update', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $permissionIds = DB::table($tableNames['permissions'])
            ->where('guard_name', 'web')
            ->whereIn('name', $permissionNames)
            ->pluck('id', 'name');
        $roleIds = DB::table($tableNames['roles'])
            ->where('guard_name', 'web')
            ->whereIn('name', ['superadmin', 'admin', 'staff', 'cs'])
            ->pluck('id', 'name');
        $assignments = [];

        foreach ($roleIds as $roleName => $roleId) {
            $assignments[] = [
                $pivotRole => $roleId,
                $pivotPermission => $permissionIds['resellers.payouts.view'],
            ];

            if (in_array($roleName, ['superadmin', 'admin'], true)) {
                $assignments[] = [
                    $pivotRole => $roleId,
                    $pivotPermission => $permissionIds['resellers.payouts.update'],
                ];
            }
        }

        if ($assignments !== []) {
            DB::table($tableNames['role_has_permissions'])->insertOrIgnore($assignments);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $permissionIds = DB::table($tableNames['permissions'])
            ->where('guard_name', 'web')
            ->whereIn('name', ['resellers.payouts.view', 'resellers.payouts.update'])
            ->pluck('id');

        DB::table($tableNames['role_has_permissions'])
            ->whereIn(config('permission.column_names.permission_pivot_key') ?? 'permission_id', $permissionIds)
            ->delete();
        DB::table($tableNames['permissions'])
            ->whereIn('id', $permissionIds)
            ->delete();
    }
};
