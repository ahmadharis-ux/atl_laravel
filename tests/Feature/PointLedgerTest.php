<?php

namespace Tests\Feature;

use App\Enums\PointTransactionType;
use App\Models\Merchandise;
use App\Models\User;
use App\Support\MerchandiseRedeemer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_earning_a_point_is_recorded_in_the_ledger(): void
    {
        $user = User::factory()->create(['points' => 0]);

        $user->addPoint();

        $user->refresh();
        $this->assertSame(1, (int) $user->points);

        $entry = $user->pointTransactions()->latest('id')->firstOrFail();
        $this->assertSame(PointTransactionType::OrderReward, $entry->type);
        $this->assertSame(1, $entry->points_change);
        $this->assertSame(1, $entry->points_after);
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
        $user->addPoint();
        $user->addPoint();

        $user->refresh();
        $last = $user->pointTransactions()->latest('id')->firstOrFail();

        $this->assertSame((int) $user->points, $last->points_after);
        $this->assertSame(2, $user->pointTransactions()->count());
    }
}
