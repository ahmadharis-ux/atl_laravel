<?php

namespace Tests\Feature;

use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Support\WalletLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WalletLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_increases_balance_and_records_a_ledger_entry(): void
    {
        $user = User::factory()->create(['balance' => 50_000]);

        $entry = WalletLedger::credit($user, 25_000, WalletTransactionType::Deposit, null, 'Top up.');

        $this->assertSame(75_000, $user->fresh()->balance);
        $this->assertSame(50_000, $entry->balance_before);
        $this->assertSame(75_000, $entry->balance_after);
        $this->assertSame(WalletTransactionDirection::Credit, $entry->direction);
        $this->assertSame(WalletTransactionType::Deposit, $entry->type);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->getKey(),
            'direction' => WalletTransactionDirection::Credit->value,
            'amount' => 25_000,
            'balance_after' => 75_000,
        ]);
    }

    public function test_debit_decreases_balance_and_records_a_ledger_entry(): void
    {
        $user = User::factory()->create(['balance' => 50_000]);

        $entry = WalletLedger::debit($user, 20_000, WalletTransactionType::Purchase, null, 'Beli.');

        $this->assertSame(30_000, $user->fresh()->balance);
        $this->assertSame(WalletTransactionDirection::Debit, $entry->direction);
        $this->assertSame(30_000, $entry->balance_after);
    }

    public function test_debit_beyond_balance_is_rejected_and_changes_nothing(): void
    {
        $user = User::factory()->create(['balance' => 5_000]);

        try {
            WalletLedger::debit($user, 10_000, WalletTransactionType::Purchase);

            $this->fail('ValidationException tidak dilempar.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('balance', $e->errors());
        }

        $this->assertSame(5_000, $user->fresh()->balance);
        $this->assertSame(0, $user->walletTransactions()->count());
    }

    public function test_entries_are_append_only_without_updated_at(): void
    {
        $user = User::factory()->create(['balance' => 0]);

        $entry = WalletLedger::credit($user, 10_000, WalletTransactionType::Deposit);

        $this->assertNull($entry->updated_at);
    }
}
