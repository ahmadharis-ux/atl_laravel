<?php

namespace Tests\Feature;

use App\Enums\DepositStatus;
use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionType;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminTopupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $permissions
     */
    private function admin(array $permissions = ['dashboard.view', 'topups.view', 'topups.update']): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create(['is_admin' => true]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_regular_users_cannot_access_the_topup_admin_pages(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $deposit = Deposit::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.topup.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.topup.show', $deposit))
            ->assertForbidden();
    }

    public function test_admin_can_see_the_topup_list(): void
    {
        $admin = $this->admin();
        $deposit = Deposit::factory()->create(['reference' => 'DEP-20261010-EXIST01']);

        $this->actingAs($admin)
            ->get(route('admin.topup.index'))
            ->assertOk()
            ->assertSee('DEP-20261010-EXIST01')
            ->assertSee($deposit->user->name);
    }

    public function test_topup_list_can_be_filtered_by_status(): void
    {
        $admin = $this->admin();
        $pending = Deposit::factory()->create(['reference' => 'DEP-20261010-PENDING']);
        $paid = Deposit::factory()->paid()->create(['reference' => 'DEP-20261010-PAIDXX']);

        $this->actingAs($admin)
            ->get(route('admin.topup.index', ['status' => DepositStatus::Pending->value]))
            ->assertOk()
            ->assertSee($pending->reference)
            ->assertDontSee($paid->reference);
    }

    public function test_admin_can_see_the_topup_detail_with_its_ledger_entry(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'reference' => 'DEP-20261010-DETAIL',
            'amount' => 50_000,
            'status' => DepositStatus::Pending,
        ]);

        $this->actingAs($admin)->post(route('admin.topup.settle', $deposit));

        $this->actingAs($admin)
            ->get(route('admin.topup.show', $deposit))
            ->assertOk()
            ->assertSee('DEP-20261010-DETAIL')
            ->assertSee('Rp 50.000')
            ->assertSee('Top up saldo DEP-20261010-DETAIL');
    }

    public function test_admin_can_settle_a_pending_topup(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'amount' => 50_000,
            'status' => DepositStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.topup.settle', $deposit))
            ->assertRedirect(route('admin.topup.index'));

        $this->assertSame(DepositStatus::Paid, $deposit->fresh()->status);
        $this->assertSame(50_000, $user->fresh()->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->getKey(),
            'type' => WalletTransactionType::Deposit->value,
            'direction' => WalletTransactionDirection::Credit->value,
            'amount' => 50_000,
            'reference_id' => $deposit->getKey(),
        ]);
    }

    public function test_settling_an_already_paid_topup_does_not_credit_twice(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->paid()->create([
            'user_id' => $user->getKey(),
            'amount' => 50_000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.topup.settle', $deposit))
            ->assertRedirect(route('admin.topup.index'));

        $this->assertSame(0, $user->fresh()->balance);
        $this->assertSame(0, $user->walletTransactions()->count());
    }

    public function test_admin_can_cancel_a_pending_topup(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'amount' => 50_000,
            'status' => DepositStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.topup.expire', $deposit))
            ->assertRedirect(route('admin.topup.index'));

        $this->assertSame(DepositStatus::Expired, $deposit->fresh()->status);
        $this->assertSame(0, $user->fresh()->balance);
    }

    public function test_settling_requires_the_update_permission(): void
    {
        $admin = $this->admin(['dashboard.view', 'topups.view']);
        $deposit = Deposit::factory()->create(['status' => DepositStatus::Pending]);

        $this->actingAs($admin)
            ->post(route('admin.topup.settle', $deposit))
            ->assertForbidden();

        $this->assertSame(DepositStatus::Pending, $deposit->fresh()->status);
    }
}
