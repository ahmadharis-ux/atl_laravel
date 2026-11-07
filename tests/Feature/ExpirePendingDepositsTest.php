<?php

namespace Tests\Feature;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpirePendingDepositsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_pending_deposit_past_its_expiry_is_expired(): void
    {
        $deposit = Deposit::factory()->create([
            'status' => DepositStatus::Pending,
            'expires_at' => Carbon::now()->subMinutes(5),
        ]);

        $this->artisan('deposits:expire-pending')->assertSuccessful();

        $this->assertSame(DepositStatus::Expired, $deposit->fresh()->status);
        $this->assertNotNull($deposit->fresh()->finalized_at);
    }

    public function test_a_recent_pending_deposit_is_left_alone(): void
    {
        $deposit = Deposit::factory()->create([
            'status' => DepositStatus::Pending,
            'expires_at' => Carbon::now()->addHours(2),
        ]);

        $this->artisan('deposits:expire-pending')->assertSuccessful();

        $this->assertSame(DepositStatus::Pending, $deposit->fresh()->status);
    }

    public function test_a_paid_deposit_is_never_expired(): void
    {
        $deposit = Deposit::factory()->paid()->create([
            'expires_at' => Carbon::now()->subHours(5),
        ]);

        $this->artisan('deposits:expire-pending')->assertSuccessful();

        $this->assertSame(DepositStatus::Paid, $deposit->fresh()->status);
    }

    public function test_a_deposit_without_an_expiry_is_skipped(): void
    {
        $deposit = Deposit::factory()->create([
            'status' => DepositStatus::Pending,
            'expires_at' => null,
        ]);

        $this->artisan('deposits:expire-pending')->assertSuccessful();

        $this->assertSame(DepositStatus::Pending, $deposit->fresh()->status);
    }
}
