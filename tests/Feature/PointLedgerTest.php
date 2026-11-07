<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PointTransactionType;
use App\Enums\UserTier;
use App\Models\Merchandise;
use App\Models\Order;
use App\Models\User;
use App\Support\MerchandiseRedeemer;
use App\Support\OrderRewardLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PointLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_order_reward_is_recorded_once_and_uses_pre_order_tier(): void
    {
        $user = User::factory()->create(['points' => 0, 'lifetime_points' => 99]);
        $order = Order::factory()->for($user)->create([
            'amount' => 1_000,
            'supplier_cost_snapshot' => 0,
            'company_cost_snapshot' => 0,
            'status' => OrderStatus::Success,
        ]);

        $this->assertSame(30, OrderRewardLedger::award($order));
        $this->assertSame(30, OrderRewardLedger::award($order));

        $user->refresh();
        $this->assertSame(30, (int) $user->points);
        $this->assertSame(129, (int) $user->lifetime_points);
        $this->assertSame(UserTier::Gold, $user->tier());

        $entry = $user->pointTransactions()->latest('id')->firstOrFail();
        $this->assertSame(PointTransactionType::OrderReward, $entry->type);
        $this->assertSame(30, $entry->points_change);
        $this->assertSame(30, $entry->points_after);
        $this->assertTrue($entry->reference->is($order));
        $this->assertSame(1, $user->pointTransactions()->count());
    }

    public function test_reward_formula_applies_exact_tier_multipliers_and_rounds_final_points_down(): void
    {
        $this->assertSame(2, OrderRewardLedger::estimate(199, 100, 0, UserTier::Silver));
        $this->assertSame(3, OrderRewardLedger::estimate(199, 100, 0, UserTier::Gold));
        $this->assertSame(4, OrderRewardLedger::estimate(199, 100, 0, UserTier::Platinum));
    }

    public function test_zero_or_negative_margin_does_not_earn_points(): void
    {
        $this->assertSame(0, OrderRewardLedger::estimate(100, 100, 0, UserTier::Silver));
        $this->assertSame(0, OrderRewardLedger::estimate(99, 100, 0, UserTier::Silver));
        $this->assertSame(0, OrderRewardLedger::estimate(200, 100, 100, UserTier::Silver));
    }

    public function test_failed_orders_and_ineligible_orders_do_not_credit_points(): void
    {
        $user = User::factory()->create();
        $failedOrder = $this->successfulOrder($user, ['status' => OrderStatus::Failed]);
        $ineligibleOrder = $this->successfulOrder($user, ['reward_eligible' => false]);

        $this->assertSame(0, OrderRewardLedger::award($failedOrder));
        $this->assertSame(0, OrderRewardLedger::award($ineligibleOrder));

        $this->assertSame(0, (int) $user->fresh()->points);
        $this->assertSame(0, $user->pointTransactions()->sum('points_change'));
        $this->assertSame(1, $user->pointTransactions()->count());
    }

    public function test_refund_reverses_reward_once_even_when_some_points_were_spent(): void
    {
        $user = User::factory()->create();
        $order = $this->successfulOrder($user);
        $item = Merchandise::factory()->create(['points_cost' => 25, 'stock' => 2, 'is_active' => true]);

        OrderRewardLedger::award($order);
        MerchandiseRedeemer::redeem($user, $item);
        $order->forceFill(['refunded_at' => now()])->save();

        $this->assertSame(30, OrderRewardLedger::reverseForRefund($order));
        $this->assertSame(0, OrderRewardLedger::reverseForRefund($order));

        $user->refresh();
        $this->assertSame(-25, (int) $user->points);
        $this->assertSame(0, (int) $user->lifetime_points);
        $this->assertSame(PointTransactionType::OrderRewardReversed, $user->pointTransactions()->latest('id')->firstOrFail()->type);

        try {
            MerchandiseRedeemer::redeem($user, $item);
            $this->fail('A user with a negative point balance must not be able to redeem merchandise.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('points', $exception->errors());
        }
    }

    public function test_merchandise_redemption_does_not_reduce_lifetime_tier(): void
    {
        $user = User::factory()->create(['points' => 100, 'lifetime_points' => 100]);
        $item = Merchandise::factory()->create(['points_cost' => 75, 'stock' => 2, 'is_active' => true]);

        MerchandiseRedeemer::redeem($user, $item);

        $user->refresh();
        $this->assertSame(25, (int) $user->points);
        $this->assertSame(100, (int) $user->lifetime_points);
        $this->assertSame(UserTier::Gold, $user->tier());
    }

    public function test_redeeming_merchandise_records_a_debit_entry(): void
    {
        $user = User::factory()->create(['points' => 200]);
        $item = Merchandise::factory()->create(['points_cost' => 120, 'stock' => 3, 'is_active' => true]);

        MerchandiseRedeemer::redeem($user, $item);

        $entry = $user->pointTransactions()->latest('id')->firstOrFail();
        $this->assertSame(PointTransactionType::MerchandiseRedeemed, $entry->type);
        $this->assertSame(-120, $entry->points_change);
        $this->assertSame(80, $entry->points_after);
        $this->assertNotNull($entry->reference);
        $this->assertTrue($entry->reference->is($item));
    }

    public function test_cancelling_a_redemption_records_a_refund_entry(): void
    {
        $user = User::factory()->create(['points' => 200]);
        $item = Merchandise::factory()->create(['points_cost' => 75, 'stock' => 2, 'is_active' => true]);

        $redemption = MerchandiseRedeemer::redeem($user, $item);
        MerchandiseRedeemer::cancel($redemption, 'dibatalkan admin');

        $entry = $user->pointTransactions()
            ->where('type', PointTransactionType::MerchandiseRefund)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(75, $entry->points_change);
        $this->assertSame(200, $entry->points_after);
    }

    public function test_ledger_reflects_current_balance(): void
    {
        $user = User::factory()->create(['points' => 10]);
        OrderRewardLedger::award($this->successfulOrder($user));
        OrderRewardLedger::award($this->successfulOrder($user, ['amount' => 500]));

        $user->refresh();
        $last = $user->pointTransactions()->latest('id')->firstOrFail();

        $this->assertSame((int) $user->points, $last->points_after);
        $this->assertSame(2, $user->pointTransactions()->count());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function successfulOrder(User $user, array $attributes = []): Order
    {
        return Order::factory()->for($user)->create(array_merge([
            'amount' => 1_000,
            'supplier_cost_snapshot' => 0,
            'company_cost_snapshot' => 0,
            'status' => OrderStatus::Success,
        ], $attributes));
    }
}
