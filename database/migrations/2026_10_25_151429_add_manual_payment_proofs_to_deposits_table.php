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
        Schema::table('deposits', function (Blueprint $table) {
            $table->timestamp('finalized_at')->nullable()->index();
        });

        Schema::create('deposit_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('file_path');
            $table->timestamp('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_action')->nullable();
            $table->text('review_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['deposit_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposit_proofs');

        Schema::table('deposits', function (Blueprint $table) {
            $table->dropIndex(['finalized_at']);
            $table->dropColumn('finalized_at');
        });
    }
};
