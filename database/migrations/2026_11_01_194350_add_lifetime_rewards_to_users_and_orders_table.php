<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('lifetime_points')->default(0)->after('points');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_cost_snapshot')->nullable()->after('amount');
            $table->unsignedBigInteger('company_cost_snapshot')->default(0)->after('supplier_cost_snapshot');
            $table->boolean('reward_eligible')->default(true)->after('company_cost_snapshot');
        });

        DB::table('orders')
            ->whereIn('status', ['pending', 'processing'])
            ->whereNull('supplier_cost_snapshot')
            ->chunkById(500, function (Collection $orders): void {
                DB::table('orders')
                    ->whereIn('id', $orders->pluck('id'))
                    ->update([
                        'supplier_cost_snapshot' => DB::raw(
                            '(SELECT cost_price FROM products WHERE products.id = orders.product_id)'
                        ),
                    ]);

                foreach ($orders->where('source', 'store_qris') as $order) {
                    $metadata = $order->metadata === null
                        ? []
                        : json_decode((string) $order->metadata, true, flags: JSON_THROW_ON_ERROR);
                    $storefront = is_array($metadata) ? ($metadata['storefront'] ?? []) : [];
                    $companyCost = max(
                        0,
                        (int) ($storefront['selling_price'] ?? 0) - (int) ($storefront['reseller_price'] ?? 0),
                    );

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['company_cost_snapshot' => $companyCost]);
                }
            });

        DB::table('users')->orderBy('id')->chunkById(500, function (Collection $users): void {
            $earnedPointsByUser = DB::table('point_transactions')
                ->whereIn('user_id', $users->pluck('id'))
                ->whereIn('type', ['order_reward', 'topup_reward'])
                ->where('points_change', '>', 0)
                ->groupBy('user_id')
                ->selectRaw('user_id, SUM(points_change) as earned_points')
                ->pluck('earned_points', 'user_id');

            foreach ($users as $user) {
                $earnedPoints = max(0, (int) ($earnedPointsByUser[$user->id] ?? 0));
                $lifetimePoints = max(0, (int) $user->points, $earnedPoints);

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['lifetime_points' => $lifetimePoints]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_cost_snapshot',
                'company_cost_snapshot',
                'reward_eligible',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('lifetime_points');
        });
    }
};
