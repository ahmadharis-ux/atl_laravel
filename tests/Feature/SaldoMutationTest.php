<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaldoMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('saldo.mutations'))->assertRedirect(route('login'));
    }

    public function test_a_user_sees_their_own_wallet_mutations(): void
    {
        $user = User::factory()->create(['balance' => 75_000]);

        WalletTransaction::factory()->create([
            'user_id' => $user->getKey(),
            'amount' => 50_000,
            'balance_before' => 25_000,
            'balance_after' => 75_000,
            'description' => 'Top up saldo DEP-20261010-AAAAAA',
        ]);

        WalletTransaction::factory()->debit()->create([
            'user_id' => $user->getKey(),
            'description' => 'Pembelian Mobile Legends',
        ]);

        WalletTransaction::factory()->create([
            'user_id' => User::factory(),
            'description' => 'Mutasi milik pengguna lain',
        ]);

        $this->actingAs($user)
            ->get(route('saldo.mutations'))
            ->assertOk()
            ->assertSee('Top up saldo DEP-20261010-AAAAAA')
            ->assertSee('Pembelian Mobile Legends')
            ->assertDontSee('Mutasi milik pengguna lain')
            ->assertSee('Rp 75.000');
    }

    public function test_the_saldo_page_links_to_the_mutation_history(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('saldo.index'))
            ->assertOk()
            ->assertSee(route('saldo.mutations'), false);
    }
}
