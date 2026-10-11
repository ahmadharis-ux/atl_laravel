<?php

namespace Tests\Feature;

use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionType;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_up_page_shows_the_available_methods_and_presets(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('deposits.create'))
            ->assertOk()
            ->assertSee('QRIS')
            ->assertSee('Transfer Bank / VA')
            ->assertSee('E-Wallet')
            ->assertSee('Rp 100.000')
            ->assertSee('data-amount-set="100000"', false);
    }

    public function test_a_user_can_create_a_pending_top_up_without_touching_balance(): void
    {
        $user = User::factory()->create(['balance' => 0]);

        $this->actingAs($user)
            ->post(route('deposits.store'), [
                'amount' => 50_000,
                'payment_method' => PaymentMethod::Qris->value,
            ])
            ->assertRedirect();

        $deposit = Deposit::query()->sole();

        $this->assertSame($user->getKey(), $deposit->user_id);
        $this->assertSame(50_000, $deposit->amount);
        $this->assertSame(DepositStatus::Pending, $deposit->status);
        $this->assertSame(PaymentMethod::Qris, $deposit->payment_method);
        $this->assertNotNull($deposit->payment_code);
        $this->assertNull($deposit->paid_at);
        $this->assertStringStartsWith('DEP-', $deposit->reference);
        $this->assertSame(0, $user->fresh()->balance);
    }

    public function test_amount_must_be_within_limits(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('deposits.store'), [
                'amount' => 1_000,
                'payment_method' => PaymentMethod::Qris->value,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, Deposit::query()->count());
    }

    public function test_virtual_account_requires_a_channel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('deposits.store'), [
                'amount' => 50_000,
                'payment_method' => PaymentMethod::VirtualAccount->value,
            ])
            ->assertSessionHasErrors('payment_channel');

        $this->assertSame(0, Deposit::query()->count());
    }

    public function test_simulated_payment_adds_balance_and_records_a_ledger_entry(): void
    {
        $user = User::factory()->create(['balance' => 10_000]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'amount' => 50_000,
            'payment_method' => PaymentMethod::Qris,
            'status' => DepositStatus::Pending,
        ]);

        $this->actingAs($user)
            ->post(route('deposits.pay', $deposit))
            ->assertRedirect(route('deposits.show', $deposit));

        $deposit->refresh();
        $this->assertSame(DepositStatus::Paid, $deposit->status);
        $this->assertNotNull($deposit->paid_at);
        $this->assertSame(60_000, $user->fresh()->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->getKey(),
            'type' => WalletTransactionType::Deposit->value,
            'direction' => WalletTransactionDirection::Credit->value,
            'amount' => 50_000,
            'reference_id' => $deposit->getKey(),
        ]);
    }

    public function test_an_already_paid_deposit_is_not_credited_twice(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->paid()->create([
            'user_id' => $user->getKey(),
            'amount' => 50_000,
        ]);

        $this->actingAs($user)
            ->post(route('deposits.pay', $deposit))
            ->assertRedirect(route('deposits.show', $deposit));

        $this->assertSame(0, $user->fresh()->balance);
        $this->assertSame(0, $user->walletTransactions()->count());
    }

    public function test_an_expired_deposit_cannot_be_paid(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'amount' => 50_000,
            'status' => DepositStatus::Pending,
            'expires_at' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->post(route('deposits.pay', $deposit))
            ->assertRedirect(route('deposits.show', $deposit));

        $this->assertSame(DepositStatus::Expired, $deposit->fresh()->status);
        $this->assertSame(0, $user->fresh()->balance);
    }

    public function test_a_user_cannot_view_or_pay_another_users_deposit(): void
    {
        $user = User::factory()->create();
        $deposit = Deposit::factory()->create();

        $this->actingAs($user)->get(route('deposits.show', $deposit))->assertForbidden();
        $this->actingAs($user)->post(route('deposits.pay', $deposit))->assertForbidden();
    }
}
