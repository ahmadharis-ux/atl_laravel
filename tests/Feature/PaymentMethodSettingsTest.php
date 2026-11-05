<?php

namespace Tests\Feature;

use App\Models\FeatureSetting;
use App\Models\Product;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PaymentMethodSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create(['is_admin' => true]);
        Permission::findOrCreate('dashboard.view', 'web');
        Permission::findOrCreate('features.view', 'web');
        Permission::findOrCreate('features.update', 'web');
        $user->givePermissionTo(['dashboard.view', 'features.view', 'features.update']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_admin_can_manage_payment_methods_from_feature_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.features.index'))
            ->assertOk()
            ->assertSee('Metode Pembayaran Isi Saldo')
            ->assertSee('Metode Pembayaran Transaksi')
            ->assertSee('Transfer Bank');

        $this->actingAs($admin)
            ->put(route('admin.features.update'), [
                'deposit_qris_enabled' => '0',
                'deposit_transfer_enabled' => '1',
                'deposit_ewallet_enabled' => '0',
                'transaction_saldo_enabled' => '1',
                'transaction_qris_enabled' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $setting = FeatureSetting::current();

        $this->assertFalse($setting->deposit_qris_enabled);
        $this->assertTrue($setting->deposit_transfer_enabled);
        $this->assertFalse($setting->deposit_ewallet_enabled);
        $this->assertTrue($setting->transaction_saldo_enabled);
        $this->assertFalse($setting->transaction_qris_enabled);
    }

    public function test_disabled_deposit_method_is_hidden_and_rejected_on_submission(): void
    {
        FeatureSetting::current()->update([
            'deposit_qris_enabled' => false,
            'deposit_transfer_enabled' => true,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('deposits.create'))
            ->assertOk()
            ->assertDontSee('name="payment_method" value="qris"', false)
            ->assertSee('name="payment_method" value="va"', false);

        $this->actingAs($user)
            ->post(route('deposits.store'), [
                'amount' => 20_000,
                'payment_method' => 'qris',
            ])
            ->assertSessionHasErrors([
                'payment_method' => 'Metode pembayaran ini sedang tidak tersedia.',
            ]);

        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_disabled_transaction_method_is_hidden_and_rejected_on_submission(): void
    {
        FeatureSetting::current()->update(['transaction_qris_enabled' => false]);
        $user = User::factory()->create(['balance' => 100_000]);
        $product = Product::factory()->create(['status' => true, 'unlimited_stock' => true]);

        $this->actingAs($user)
            ->get(route('checkout.show', $product))
            ->assertOk()
            ->assertSee('name="payment_method" value="saldo"', false)
            ->assertDontSee('name="payment_method" value="qris"', false);

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '081234567890',
                'payment_method' => 'qris',
            ])
            ->assertSessionHasErrors([
                'payment_method' => 'Metode pembayaran ini sedang tidak tersedia.',
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_disabled_balance_method_is_hidden_and_rejected_on_submission(): void
    {
        FeatureSetting::current()->update(['transaction_saldo_enabled' => false]);
        $user = User::factory()->create(['balance' => 100_000]);
        $product = Product::factory()->create(['status' => true, 'unlimited_stock' => true]);

        $this->actingAs($user)
            ->get(route('checkout.show', $product))
            ->assertOk()
            ->assertSee('name="payment_method" value="qris"', false)
            ->assertDontSee('name="payment_method" value="saldo"', false);

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'target' => '081234567890',
                'payment_method' => 'saldo',
                'pin' => UserFactory::DEFAULT_PIN,
            ])
            ->assertSessionHasErrors([
                'payment_method' => 'Metode pembayaran ini sedang tidak tersedia.',
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_disabled_bank_transfer_method_is_hidden_and_rejected_on_submission(): void
    {
        FeatureSetting::current()->update(['deposit_transfer_enabled' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('deposits.create'))
            ->assertOk()
            ->assertDontSee('name="payment_method" value="va"', false)
            ->assertSee('name="payment_method" value="qris"', false);

        $this->actingAs($user)
            ->post(route('deposits.store'), [
                'amount' => 20_000,
                'payment_method' => 'va',
            ])
            ->assertSessionHasErrors([
                'payment_method' => 'Metode pembayaran ini sedang tidak tersedia.',
            ]);

        $this->assertDatabaseCount('deposits', 0);
    }
}
