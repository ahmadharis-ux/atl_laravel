<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\MasterBank;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Support\TwoFactorAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_settings_page_lists_every_section(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Pengaturan')
            ->assertSee('Profil')
            ->assertSee('Rekening Bank')
            ->assertSee('Ubah Kata Sandi')
            ->assertSee('Autentikasi Dua Faktor')
            ->assertSee('Perangkat Aktif');
    }

    public function test_each_settings_detail_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.profile'))->assertOk()->assertSee('Informasi Akun');
        $this->actingAs($user)->get(route('settings.password'))->assertOk()->assertSee('Kata Sandi');
        $this->actingAs($user)->get(route('settings.banks.index'))->assertOk()->assertSee('Daftar Rekening');
        $this->actingAs($user)->get(route('settings.two-factor'))->assertOk()->assertSee('Autentikasi Dua Faktor');
        $this->actingAs($user)->get(route('settings.sessions'))->assertOk()->assertSee('Sesi Login');
    }

    public function test_a_user_can_update_their_profile(): void
    {
        $user = User::factory()->create(['name' => 'Nama Lama']);

        $this->actingAs($user)
            ->put(route('settings.profile.update'), [
                'name' => 'Nama Baru',
                'email' => $user->email,
                'phone' => '081200000000',
            ])
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('081200000000', $user->phone);
    }

    public function test_changing_email_resets_verification_and_resends_the_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'lama@example.com']);

        $this->actingAs($user)->put(route('settings.profile.update'), [
            'name' => $user->name,
            'email' => 'baru@example.com',
            'phone' => null,
        ]);

        $user->refresh();
        $this->assertSame('baru@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_a_user_can_change_their_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)->put(route('settings.password.update'), [
            'current_password' => 'password',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertSessionHas('status');

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));
    }

    public function test_changing_password_requires_the_current_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)->put(route('settings.password.update'), [
            'current_password' => 'salah',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_a_user_can_add_and_remove_a_bank_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.banks.store'), [
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Budi Santoso',
            'is_primary' => '1',
        ])->assertSessionHas('status');

        $bank = BankAccount::query()->sole();
        $this->assertSame($user->getKey(), $bank->user_id);
        $this->assertTrue($bank->is_primary);

        $this->actingAs($user)
            ->delete(route('settings.banks.destroy', $bank))
            ->assertSessionHas('status');

        $this->assertSame(0, BankAccount::query()->count());
    }

    public function test_a_user_cannot_add_a_bank_account_matching_a_master_bank(): void
    {
        $user = User::factory()->create();
        MasterBank::factory()->create(['bank_name' => 'DANA', 'account_number' => '085943133886']);

        $this->actingAs($user)->post(route('settings.banks.store'), [
            'bank_name' => 'DANA',
            'account_number' => '085943133886',
            'account_holder' => 'Ahmad Haris',
        ])->assertSessionHasErrors('account_number');

        $this->assertSame(0, BankAccount::query()->count());
    }

    public function test_a_user_cannot_modify_another_users_bank_account(): void
    {
        $user = User::factory()->create();
        $bank = BankAccount::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.banks.destroy', $bank))
            ->assertForbidden();

        $this->assertDatabaseHas('bank_accounts', ['id' => $bank->getKey()]);
    }

    public function test_two_factor_can_be_enabled_confirmed_and_disabled(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)->post(route('settings.two-factor.enable'), [
            'password' => 'password',
        ])->assertSessionHas('status');

        $secret = $user->fresh()->two_factor_secret;
        $this->assertNotNull($secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);

        $this->actingAs($user)->post(route('settings.two-factor.confirm'), [
            'code' => TwoFactorAuthentication::currentCode($secret),
        ])->assertSessionHas('status');

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->assertNotEmpty($user->fresh()->two_factor_recovery_codes);

        $this->actingAs($user)->delete(route('settings.two-factor.disable'), [
            'password' => 'password',
        ])->assertSessionHas('status');

        $this->assertNull($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }
}
