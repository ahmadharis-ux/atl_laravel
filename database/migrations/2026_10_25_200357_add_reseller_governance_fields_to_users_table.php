<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const MANAGEMENT_PERMISSION = 'resellers.update';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('reseller_status', 20)->nullable()->index();
            $table->string('reseller_website')->nullable();
            $table->string('reseller_sales_channel', 20)->nullable();
            $table->string('reseller_contact_channel', 20)->nullable();
            $table->string('reseller_contact')->nullable();
            $table->json('reseller_allowed_ips')->nullable();
            $table->string('reseller_terms_version', 30)->nullable();
            $table->timestamp('reseller_terms_accepted_at')->nullable();
            $table->timestamp('reseller_last_api_at')->nullable();
            $table->text('reseller_status_reason')->nullable();
            $table->timestamp('reseller_reviewed_at')->nullable();
            $table->foreignId('reseller_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('users')
            ->where('is_reseller', true)
            ->update(['reseller_status' => 'active']);

        DB::table('users')
            ->whereNotNull('reseller_since')
            ->where('is_reseller', false)
            ->update(['reseller_status' => 'inactive']);

        Schema::create('reseller_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('reason')->nullable();
            $table->string('terms_version', 30)->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        $this->addManagementPermission();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->removeManagementPermission();
        Schema::dropIfExists('reseller_status_histories');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['reseller_reviewed_by']);
            $table->dropIndex(['reseller_status']);
            $table->dropColumn([
                'reseller_status',
                'reseller_website',
                'reseller_sales_channel',
                'reseller_contact_channel',
                'reseller_contact',
                'reseller_allowed_ips',
                'reseller_terms_version',
                'reseller_terms_accepted_at',
                'reseller_last_api_at',
                'reseller_status_reason',
                'reseller_reviewed_at',
                'reseller_reviewed_by',
            ]);
        });
    }

    private function addManagementPermission(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        DB::table($tableNames['permissions'])->insertOrIgnore([
            'name' => self::MANAGEMENT_PERMISSION,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId = DB::table($tableNames['permissions'])
            ->where('guard_name', 'web')
            ->where('name', self::MANAGEMENT_PERMISSION)
            ->value('id');

        $roleIds = DB::table($tableNames['roles'])
            ->where('guard_name', 'web')
            ->whereIn('name', ['superadmin', 'admin'])
            ->pluck('id');
        $assignments = $roleIds->map(fn ($roleId): array => [
            $pivotRole => $roleId,
            $pivotPermission => $permissionId,
        ])->all();

        if ($assignments !== []) {
            DB::table($tableNames['role_has_permissions'])->insertOrIgnore($assignments);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function removeManagementPermission(): void
    {
        $tableNames = config('permission.table_names');
        $permissionIds = DB::table($tableNames['permissions'])
            ->where('guard_name', 'web')
            ->where('name', self::MANAGEMENT_PERMISSION)
            ->pluck('id');

        DB::table($tableNames['role_has_permissions'])
            ->whereIn(config('permission.column_names.permission_pivot_key') ?? 'permission_id', $permissionIds)
            ->delete();
        DB::table($tableNames['permissions'])
            ->whereIn('id', $permissionIds)
            ->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
