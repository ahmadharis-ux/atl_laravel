<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('legacy_users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_user_id')->nullable()->unique();
            $table->string('username')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('password_hash');
            $table->decimal('balance', 18, 2)->default(0);
            $table->unsignedBigInteger('points')->default(0);
            $table->string('legacy_level')->nullable();
            $table->string('legacy_status')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('migrated_at')->nullable();
            $table->foreignId('migrated_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legacy_users');
    }
};
