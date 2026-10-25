<?php

namespace Tests\Feature;

use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionType;
use App\Models\Deposit;
use App\Models\MasterBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_show_displays_the_master_bank_instructions(): void
    {
        $user = User::factory()->create();
        $bank = MasterBank::factory()->create([
            'type' => MasterBank::TYPE_BANK,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'PT Atlantic Pedia',
        ]);

        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'master_bank_id' => $bank->getKey(),
        ]);

        $this->actingAs($user)
            ->get(route('deposits.show', $deposit))
            ->assertOk()
            ->assertSee('Isi Saldo')
            ->assertSee('ID Transaksi')
            ->assertSee('BCA')
            ->assertSee('1234567890')
            ->assertSee('PT Atlantic Pedia');
    }

    public function test_va_and_ewallet_deposit_page_asks_for_proof_before_admin_review(): void
    {
        $user = User::factory()->create();
        $bank = MasterBank::factory()->create([
            'type' => MasterBank::TYPE_EWALLET,
            'bank_name' => 'DANA',
            'account_number' => '085943133886',
            'account_holder' => 'Ahmad Haris',
        ]);

        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::EWallet,
            'master_bank_id' => $bank->getKey(),
            'status' => DepositStatus::Pending,
        ]);

        $this->actingAs($user)
            ->get(route('deposits.show', $deposit))
            ->assertOk()
            ->assertSee('Menunggu pembayaran')
            ->assertSee('Kirim bukti untuk diverifikasi')
            ->assertSee('Saldo belum ditambahkan')
            ->assertDontSee('Simulasikan Pembayaran');
    }

    public function test_va_and_ewallet_deposit_page_offers_copy_for_number_and_amount(): void
    {
        $user = User::factory()->create();
        $bank = MasterBank::factory()->create([
            'type' => MasterBank::TYPE_BANK,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'PT Atlantic Pedia',
        ]);

        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::VirtualAccount,
            'master_bank_id' => $bank->getKey(),
            'amount' => 50_000,
            'status' => DepositStatus::Pending,
        ]);

        $this->actingAs($user)
            ->get(route('deposits.show', $deposit))
            ->assertOk()
            ->assertSee('data-copy="1234567890"', false)
            ->assertSee('data-copy="50000"', false);
    }

    public function test_deposit_show_shows_qris_without_sandbox_testing_data(): void
    {
        config()->set('midtrans.is_production', false);

        $user = User::factory()->create();
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::Qris,
            'status' => DepositStatus::Pending,
            'metadata' => [
                'midtrans' => [
                    'qr_url' => 'https://api.sandbox.midtrans.com/v2/qris/qr.png',
                    'qr_string' => '00020101021226610014ID.CO.QRIS.WWW',
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('deposits.show', $deposit))
            ->assertOk()
            ->assertSee('https://api.sandbox.midtrans.com/v2/qris/qr.png', false)
            ->assertDontSee('Data Testing (Sandbox Midtrans)')
            ->assertDontSee('00020101021226610014ID.CO.QRIS.WWW', false)
            ->assertDontSee('simulator.sandbox.midtrans.com/v2/qris/index', false);
    }

    public function test_deposit_show_offers_qris_download_and_status_buttons(): void
    {
        $user = User::factory()->create();
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::Qris,
            'status' => DepositStatus::Pending,
            'metadata' => ['midtrans' => ['qr_url' => 'https://api.sandbox.midtrans.com/v2/qris/qr.png']],
        ]);

        $this->actingAs($user)
            ->get(route('deposits.show', $deposit))
            ->assertOk()
            ->assertSee(route('deposits.qris', $deposit), false)
            ->assertSee(route('deposits.sync', $deposit), false)
            ->assertSee('Unduh QRIS')
            ->assertSee('Cek Status');
    }

    public function test_deposit_qris_can_be_downloaded(): void
    {
        Http::fake(['*' => Http::response('PNG-BYTES', 200, ['Content-Type' => 'image/png'])]);

        $user = User::factory()->create();
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::Qris,
            'status' => DepositStatus::Pending,
            'metadata' => ['midtrans' => ['qr_url' => 'https://api.sandbox.midtrans.com/v2/qris/qr.png']],
        ]);

        $this->actingAs($user)
            ->get(route('deposits.qris', $deposit))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Content-Disposition', 'attachment; filename="qris-'.$deposit->reference.'.png"')
            ->assertContent('PNG-BYTES');
    }

    public function test_deposit_qris_download_returns_404_without_qr_url(): void
    {
        $user = User::factory()->create();
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::Qris,
            'status' => DepositStatus::Pending,
            'metadata' => null,
        ]);

        $this->actingAs($user)
            ->get(route('deposits.qris', $deposit))
            ->assertNotFound();
    }

    public function test_deposit_qris_download_requires_ownership(): void
    {
        $deposit = Deposit::factory()->create([
            'payment_method' => PaymentMethod::Qris,
            'metadata' => ['midtrans' => ['qr_url' => 'https://api.sandbox.midtrans.com/v2/qris/qr.png']],
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('deposits.qris', $deposit))
            ->assertForbidden();
    }

    public function test_deposit_sync_redirects_with_status_message(): void
    {
        $user = User::factory()->create();
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::Qris,
            'status' => DepositStatus::Pending,
            'metadata' => ['midtrans' => ['qr_url' => 'https://api.sandbox.midtrans.com/v2/qris/qr.png']],
        ]);

        $this->actingAs($user)
            ->post(route('deposits.sync', $deposit))
            ->assertRedirect(route('deposits.show', $deposit))
            ->assertSessionHas('status', 'Status tidak dapat diambil dari Midtrans.');
    }

    public function test_deposit_sync_requires_ownership(): void
    {
        $deposit = Deposit::factory()->create([
            'payment_method' => PaymentMethod::Qris,
            'status' => DepositStatus::Pending,
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('deposits.sync', $deposit))
            ->assertForbidden();
    }

    public function test_top_up_history_lists_all_deposits_for_the_user(): void
    {
        $user = User::factory()->create();
        $pending = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::Qris,
            'expires_at' => now()->addHour(),
        ]);
        $paid = Deposit::factory()->paid()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::VirtualAccount,
        ]);
        $ewallet = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::EWallet,
            'payment_channel' => 'dana',
        ]);
        $expired = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'status' => DepositStatus::Expired,
            'expires_at' => now()->subHour(),
        ]);
        $foreign = Deposit::factory()->create();

        $this->actingAs($user)
            ->get(route('deposits.index'))
            ->assertOk()
            ->assertSee('Riwayat Isi Saldo')
            ->assertSee('Semua permintaan untuk menambahkan saldo akun.')
            ->assertSee('QRIS')
            ->assertSee('Transfer Bank')
            ->assertSee('E-Wallet')
            ->assertSee($pending->reference)
            ->assertSee($paid->reference)
            ->assertSee($ewallet->reference)
            ->assertSee($expired->reference)
            ->assertSee(route('deposits.show', $pending), false)
            ->assertDontSee($foreign->reference);
    }

    public function test_saldo_page_shows_a_callout_for_unexpired_pending_top_ups(): void
    {
        $user = User::factory()->create();
        $pending = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'expires_at' => now()->addHour(),
        ]);
        $stale = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'status' => DepositStatus::Pending,
            'expires_at' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->get(route('saldo.index'))
            ->assertOk()
            ->assertSee('Isi Saldo')
            ->assertSee('menunggu pembayaran')
            ->assertSee($pending->reference)
            ->assertDontSee($stale->reference);
    }

    public function test_top_up_page_shows_the_available_methods_and_presets(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('deposits.create'))
            ->assertOk()
            ->assertSee('Ini bukan metode pembayaran produk; saat checkout, pilih Saldo atau QRIS.')
            ->assertSee('Nominal Isi Saldo')
            ->assertSee('Isi Saldo')
            ->assertSee('QRIS')
            ->assertSee('Transfer Bank')
            ->assertSee('E-Wallet')
            ->assertSee('Rp 100.000')
            ->assertSee('data-amount-set="100000"', false);
    }

    public function test_top_up_page_shows_the_admin_fee_on_the_target_option(): void
    {
        $user = User::factory()->create();
        MasterBank::factory()->create([
            'type' => MasterBank::TYPE_BANK,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'PT Atlantic Pedia',
            'admin_fee' => 2_500,
        ]);

        $this->actingAs($user)
            ->get(route('deposits.create'))
            ->assertOk()
            ->assertSee('biaya admin Rp 2.500');
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

    public function test_virtual_account_requires_a_bank_target(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('deposits.store'), [
                'amount' => 50_000,
                'payment_method' => PaymentMethod::VirtualAccount->value,
            ])
            ->assertSessionHasErrors('master_bank_id');

        $this->assertSame(0, Deposit::query()->count());
    }

    public function test_transfer_bank_uses_the_selected_master_bank(): void
    {
        $user = User::factory()->create();
        $bank = MasterBank::factory()->create([
            'type' => MasterBank::TYPE_BANK,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'PT Atlantic Pedia',
        ]);

        $this->actingAs($user)
            ->post(route('deposits.store'), [
                'amount' => 50_000,
                'payment_method' => PaymentMethod::VirtualAccount->value,
                'master_bank_id' => $bank->getKey(),
            ])
            ->assertRedirect();

        $deposit = Deposit::query()->sole();
        $this->assertSame($bank->getKey(), $deposit->master_bank_id);
        $this->assertSame('BCA', $deposit->payment_channel);
        $this->assertSame('1234567890', $deposit->payment_code);
    }

    public function test_top_up_stores_the_admin_fee_from_the_selected_bank(): void
    {
        $user = User::factory()->create();
        $bank = MasterBank::factory()->create([
            'type' => MasterBank::TYPE_BANK,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'PT Atlantic Pedia',
            'admin_fee' => 2_500,
        ]);

        $this->actingAs($user)
            ->post(route('deposits.store'), [
                'amount' => 50_000,
                'payment_method' => PaymentMethod::VirtualAccount->value,
                'master_bank_id' => $bank->getKey(),
            ])
            ->assertRedirect();

        $deposit = Deposit::query()->sole();
        $this->assertSame(2_500, $deposit->fee);
        $this->assertSame(52_500, $deposit->totalDue());
    }

    public function test_deposit_show_displays_the_admin_fee_and_total_transfer(): void
    {
        $user = User::factory()->create();
        $bank = MasterBank::factory()->create([
            'type' => MasterBank::TYPE_EWALLET,
            'bank_name' => 'DANA',
            'account_number' => '085943133886',
            'account_holder' => 'Ahmad Haris',
            'admin_fee' => 2_500,
        ]);

        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::EWallet,
            'master_bank_id' => $bank->getKey(),
            'amount' => 50_000,
            'fee' => 2_500,
            'status' => DepositStatus::Pending,
        ]);

        $this->actingAs($user)
            ->get(route('deposits.show', $deposit))
            ->assertOk()
            ->assertSee('Biaya admin')
            ->assertSee('Jumlah transfer')
            ->assertSee('Rp 52.500')
            ->assertSee('data-copy="52500"', false);
    }

    public function test_ewallet_rejects_a_bank_type_target(): void
    {
        $user = User::factory()->create();
        $bank = MasterBank::factory()->create(['type' => MasterBank::TYPE_BANK]);

        $this->actingAs($user)
            ->post(route('deposits.store'), [
                'amount' => 50_000,
                'payment_method' => PaymentMethod::EWallet->value,
                'master_bank_id' => $bank->getKey(),
            ])
            ->assertSessionHasErrors('master_bank_id');

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
            ->assertRedirect(route('deposits.show', $deposit))
            ->assertSessionHas('status', 'Isi saldo '.$deposit->reference.' berhasil. Saldo kamu sudah bertambah.');

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

    public function test_show_returns_json_status_for_polling(): void
    {
        $user = User::factory()->create();
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'status' => DepositStatus::Pending,
        ]);

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('deposits.show', $deposit))
            ->assertOk()
            ->assertJson(['active' => true, 'status' => 'pending']);

        $deposit->update(['status' => DepositStatus::Paid]);

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('deposits.show', $deposit))
            ->assertOk()
            ->assertJson(['active' => false, 'status' => 'paid']);
    }

    public function test_pending_top_up_page_renders_the_poll_hook(): void
    {
        $user = User::factory()->create();
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'status' => DepositStatus::Pending,
        ]);

        $this->actingAs($user)
            ->get(route('deposits.show', $deposit))
            ->assertOk()
            ->assertSee('data-poll-url="'.route('deposits.show', $deposit).'"', false)
            ->assertSee('data-poll-status="pending"', false);
    }

    public function test_paid_deposit_page_does_not_render_the_poll_hook(): void
    {
        $user = User::factory()->create();
        $deposit = Deposit::factory()->paid()->create([
            'user_id' => $user->getKey(),
        ]);

        $this->actingAs($user)
            ->get(route('deposits.show', $deposit))
            ->assertOk()
            ->assertDontSee('data-poll-url', false);
    }
}
