<?php

namespace Tests\Feature;

use App\Models\MasterBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminMasterBankTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['dashboard.view', 'master-banks.view', 'master-banks.update']): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create(['is_admin' => true]);
        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_admin_can_view_the_master_bank_page(): void
    {
        MasterBank::factory()->create(['bank_name' => 'BCA']);

        $this->actingAs($this->admin())
            ->get(route('admin.master-banks.index'))
            ->assertOk()
            ->assertSee('Master Bank')
            ->assertSee('BCA')
            ->assertSee('E-Wallet');
    }

    public function test_admin_can_create_update_and_delete_a_master_bank(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.master-banks.store'), [
            'type' => MasterBank::TYPE_BANK,
            'bank_name' => 'BNI',
            'account_number' => '111222333',
            'account_holder' => 'PT Atlantic Pedia',
            'admin_fee' => '2500',
            'is_active' => '1',
            'sort_order' => 2,
        ])->assertSessionHas('status');

        $bank = MasterBank::query()->sole();
        $this->assertSame('BNI', $bank->bank_name);
        $this->assertSame(MasterBank::TYPE_BANK, $bank->type);
        $this->assertSame(2_500, $bank->admin_fee);
        $this->assertTrue($bank->is_active);

        $this->actingAs($admin)->put(route('admin.master-banks.update', $bank), [
            'type' => MasterBank::TYPE_EWALLET,
            'bank_name' => 'BNI',
            'account_number' => '111222333',
            'account_holder' => 'PT Atlantic Pedia Baru',
            'admin_fee' => '5000',
            'is_active' => '1',
            'sort_order' => 5,
        ])->assertSessionHas('status');

        $bank->refresh();
        $this->assertSame(MasterBank::TYPE_EWALLET, $bank->type);
        $this->assertSame('PT Atlantic Pedia Baru', $bank->account_holder);
        $this->assertSame(5_000, $bank->admin_fee);
        $this->assertSame(5, $bank->sort_order);

        $this->actingAs($admin)
            ->delete(route('admin.master-banks.destroy', $bank))
            ->assertSessionHas('status');

        $this->assertSame(0, MasterBank::query()->count());
    }

    public function test_a_duplicate_account_number_for_the_same_bank_is_rejected(): void
    {
        MasterBank::factory()->create(['bank_name' => 'BCA', 'account_number' => '999']);

        $this->actingAs($this->admin())->post(route('admin.master-banks.store'), [
            'bank_name' => 'BCA',
            'account_number' => '999',
            'account_holder' => 'PT Atlantic Pedia',
        ])->assertSessionHasErrors('account_number');

        $this->assertSame(1, MasterBank::query()->count());
    }

    public function test_a_user_without_permission_cannot_access_master_bank(): void
    {
        $user = $this->admin(['dashboard.view']);

        $this->actingAs($user)
            ->get(route('admin.master-banks.index'))
            ->assertForbidden();
    }
}
