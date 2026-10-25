<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Order;
use App\Models\ResellerCommission;
use App\Models\ResellerPayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerStorefrontControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseller_can_request_a_payout_to_an_owned_bank_account(): void
    {
        $reseller = $this->reseller();
        $this->commission($reseller, 5_000);
        $bankAccount = BankAccount::factory()->for($reseller)->create([
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Nama Reseller',
        ]);

        $this->actingAs($reseller)
            ->from(route('settings.reseller.store'))
            ->post(route('settings.reseller.store.payouts.store'), [
                'bank_account_id' => $bankAccount->getKey(),
                'amount' => 3_000,
            ])
            ->assertRedirect(route('settings.reseller.store'))
            ->assertSessionHas('status');

        $payout = ResellerPayout::query()->sole();

        $this->assertSame('pending', $payout->status);
        $this->assertSame(3_000, $payout->amount);
        $this->assertSame('1234567890', $payout->account_number);

        $this->actingAs($reseller)
            ->from(route('settings.reseller.store'))
            ->post(route('settings.reseller.store.payouts.store'), [
                'bank_account_id' => $bankAccount->getKey(),
                'amount' => 3_000,
            ])
            ->assertRedirect(route('settings.reseller.store'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('reseller_payouts', 1);
    }

    public function test_reseller_cannot_request_a_payout_to_another_users_bank_account(): void
    {
        $reseller = $this->reseller();
        $this->commission($reseller, 5_000);
        $otherUser = User::factory()->create();
        $bankAccount = BankAccount::factory()->for($otherUser)->create();

        $this->actingAs($reseller)
            ->from(route('settings.reseller.store'))
            ->post(route('settings.reseller.store.payouts.store'), [
                'bank_account_id' => $bankAccount->getKey(),
                'amount' => 1_000,
            ])
            ->assertRedirect(route('settings.reseller.store'))
            ->assertSessionHasErrors('bank_account_id');

        $this->assertDatabaseCount('reseller_payouts', 0);
    }

    public function test_payout_request_requires_a_positive_amount(): void
    {
        $reseller = $this->reseller();
        $bankAccount = BankAccount::factory()->for($reseller)->create();

        $this->actingAs($reseller)
            ->from(route('settings.reseller.store'))
            ->post(route('settings.reseller.store.payouts.store'), [
                'bank_account_id' => $bankAccount->getKey(),
                'amount' => 0,
            ])
            ->assertRedirect(route('settings.reseller.store'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('reseller_payouts', 0);
    }

    public function test_reseller_dashboard_shows_credited_commission_history(): void
    {
        $reseller = $this->reseller();
        $order = $this->commission($reseller, 2_500);

        $this->actingAs($reseller)
            ->get(route('settings.reseller.store'))
            ->assertOk()
            ->assertSee('Riwayat Komisi QRIS')
            ->assertSee($order->order_number)
            ->assertSee('+Rp 2.500');
    }

    private function reseller(): User
    {
        return User::factory()->create([
            'is_reseller' => true,
            'reseller_since' => now(),
            'balance' => 0,
        ]);
    }

    private function commission(User $reseller, int $amount): Order
    {
        $order = Order::factory()->for($reseller)->create();

        ResellerCommission::query()->create([
            'user_id' => $reseller->getKey(),
            'order_id' => $order->getKey(),
            'amount' => $amount,
        ]);

        return $order;
    }
}
