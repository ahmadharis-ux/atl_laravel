<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Toko hosted reseller (/toko) dihapus: reseller kini hanya lewat API.
 * Tabel komisi/pencairan dan kolom khusus toko tidak dipakai lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('reseller_payouts');
        Schema::dropIfExists('reseller_commissions');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['storefront_key']);
            $table->dropColumn(['storefront_key', 'refund_status', 'refund_requested_at', 'refund_transaction_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('reseller_sales_channel');
        });

        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['resellers.payouts.view', 'resellers.payouts.update'])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('reseller_sales_channel', 20)->nullable();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('storefront_key', 64)->nullable()->unique();
            $table->string('refund_status', 24)->nullable();
            $table->timestamp('refund_requested_at')->nullable();
            $table->string('refund_transaction_id')->nullable();
        });

        Schema::create('reseller_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('reseller_payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('status', 20)->default('pending');
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_holder');
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }
};
