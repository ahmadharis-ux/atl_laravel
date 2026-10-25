<?php

namespace Tests\Feature;

use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Enums\WalletTransactionType;
use App\Models\Deposit;
use App\Models\DepositProof;
use App\Models\User;
use App\Notifications\TransactionResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ManualDepositProofTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $permissions
     */
    private function admin(array $permissions = ['dashboard.view', 'topups.view', 'topups.update']): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = User::factory()->create(['is_admin' => true]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    private function submittedProof(Deposit $deposit): DepositProof
    {
        $path = 'deposit-proofs/'.$deposit->reference.'.png';
        Storage::disk('local')->put($path, 'proof');

        $proof = $deposit->proofs()->create([
            'disk' => 'local',
            'file_path' => $path,
            'submitted_at' => now(),
        ]);

        $deposit->update([
            'status' => DepositStatus::UnderReview,
            'expires_at' => null,
        ]);

        return $proof;
    }

    public function test_user_can_upload_private_proof_and_admins_are_notified(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        Notification::fake();
        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::VirtualAccount,
            'expires_at' => now()->addHour(),
        ]);

        $this->actingAs($user)
            ->post(route('deposits.proof.store', $deposit), [
                'proof' => UploadedFile::fake()->image('transfer.png'),
            ])
            ->assertRedirect(route('deposits.show', $deposit))
            ->assertSessionHas('status');

        $deposit->refresh();
        $proof = $deposit->proofs()->firstOrFail();
        $this->assertSame(DepositStatus::UnderReview, $deposit->status);
        $this->assertNull($deposit->expires_at);
        $this->assertSame(0, $user->fresh()->balance);
        Storage::disk('local')->assertExists($proof->file_path);
        Notification::assertSentTo($admin, TransactionResult::class, fn (TransactionResult $notification): bool => $notification->context['reference'] === $deposit->reference);
    }

    public function test_upload_rejects_invalid_files_and_qris_deposits(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::EWallet,
        ]);
        $qrisDeposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::Qris,
        ]);

        $this->actingAs($user)
            ->post(route('deposits.proof.store', $deposit), [
                'proof' => UploadedFile::fake()->create('transfer.txt', 20, 'text/plain'),
            ])
            ->assertSessionHasErrors('proof');

        $this->post(route('deposits.proof.store', $deposit), [
            'proof' => UploadedFile::fake()->create('transfer.pdf', 5121, 'application/pdf'),
        ])->assertSessionHasErrors('proof');

        $this->post(route('deposits.proof.store', $qrisDeposit), [
            'proof' => UploadedFile::fake()->image('transfer.png'),
        ])->assertNotFound();

        $this->assertSame(DepositStatus::Pending, $deposit->fresh()->status);
        $this->assertSame(0, $deposit->proofs()->count());
    }

    public function test_user_cannot_upload_proof_for_another_users_deposit(): void
    {
        $deposit = Deposit::factory()->create([
            'payment_method' => PaymentMethod::EWallet,
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('deposits.proof.store', $deposit), [
                'proof' => UploadedFile::fake()->image('transfer.png'),
            ])
            ->assertForbidden();

        $this->assertSame(0, $deposit->proofs()->count());
    }

    public function test_expired_manual_deposit_cannot_accept_a_proof(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::VirtualAccount,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('deposits.proof.store', $deposit), [
                'proof' => UploadedFile::fake()->image('transfer.png'),
            ]);

        $response->assertRedirect(route('deposits.show', $deposit));
        $deposit->refresh();
        $this->assertSame(DepositStatus::Expired, $deposit->status);
        $this->assertNotNull($deposit->finalized_at);
        $this->assertSame(0, $deposit->proofs()->count());
        $this->assertSame([], Storage::disk('local')->allFiles('deposit-proofs'));
    }

    public function test_manual_topup_cannot_be_settled_without_a_reviewed_proof(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::VirtualAccount,
            'amount' => 50_000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.topup.settle', $deposit))
            ->assertRedirect(route('admin.topup.show', $deposit));

        $this->get(route('admin.topup.show', $deposit))
            ->assertOk()
            ->assertDontSee('Tandai Lunas');
        $this->assertSame(DepositStatus::Pending, $deposit->fresh()->status);
        $this->assertSame(0, $user->fresh()->balance);
        $this->assertSame(0, $user->walletTransactions()->count());
    }

    public function test_manual_topup_cannot_be_paid_through_the_development_simulator(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::EWallet,
            'amount' => 50_000,
        ]);

        $this->actingAs($user)
            ->post(route('deposits.pay', $deposit))
            ->assertNotFound();

        $this->assertSame(DepositStatus::Pending, $deposit->fresh()->status);
        $this->assertSame(0, $user->fresh()->balance);
        $this->assertSame(0, $user->walletTransactions()->count());
    }

    public function test_admin_can_request_a_revision_then_user_can_submit_a_replacement(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::EWallet,
        ]);

        $this->actingAs($user)
            ->post(route('deposits.proof.store', $deposit), [
                'proof' => UploadedFile::fake()->image('first-proof.png'),
            ])
            ->assertRedirect(route('deposits.show', $deposit));

        $firstProof = $deposit->proofs()->firstOrFail();
        $reason = 'Nominal pada gambar belum terlihat jelas. Mohon unggah ulang.';
        $this->actingAs($admin)
            ->post(route('admin.topup.review', $deposit), [
                'decision' => 'needs_revision',
                'reason' => $reason,
            ])
            ->assertRedirect(route('admin.topup.show', $deposit));

        $this->assertSame(DepositStatus::NeedsRevision, $deposit->fresh()->status);
        $this->assertSame('needs_revision', $firstProof->fresh()->review_action);
        $this->assertSame($reason, $firstProof->fresh()->review_reason);
        $this->assertSame(0, $user->fresh()->balance);

        $this->actingAs($user)
            ->get(route('deposits.show', $deposit))
            ->assertOk()
            ->assertSee($reason)
            ->assertSee('Unggah bukti yang diperbaiki');

        $this->post(route('deposits.proof.store', $deposit), [
            'proof' => UploadedFile::fake()->image('replacement-proof.png'),
        ])->assertRedirect(route('deposits.show', $deposit));

        $deposit->refresh();
        $this->assertSame(DepositStatus::UnderReview, $deposit->status);
        $this->assertSame(2, $deposit->proofs()->count());
        $this->assertNull($deposit->proofs()->firstOrFail()->review_action);
    }

    public function test_admin_approval_credits_manual_topup_once_and_keeps_the_proof(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::VirtualAccount,
            'amount' => 50_000,
            'fee' => 2_500,
        ]);
        $proof = $this->submittedProof($deposit);

        $this->actingAs($admin)
            ->post(route('admin.topup.settle', $deposit), ['received_amount' => 2_500])
            ->assertSessionHasErrors('received_amount');

        $this->assertSame(DepositStatus::UnderReview, $deposit->fresh()->status);
        $this->assertNull($proof->fresh()->review_action);
        $this->assertSame(0, $user->fresh()->balance);

        $this->actingAs($admin)
            ->post(route('admin.topup.settle', $deposit), ['received_amount' => 52_500])
            ->assertRedirect(route('admin.topup.index'));

        $deposit->refresh();
        $this->assertSame(DepositStatus::Paid, $deposit->status);
        $this->assertNotNull($deposit->finalized_at);
        $this->assertSame(50_000, $user->fresh()->balance);
        $this->assertSame(DepositProof::ACTION_APPROVED, $proof->fresh()->review_action);
        $this->assertSame($admin->getKey(), $proof->fresh()->reviewed_by);
        Storage::disk('local')->assertExists($proof->file_path);

        $this->post(route('admin.topup.settle', $deposit))
            ->assertRedirect(route('admin.topup.index'));

        $this->assertSame(50_000, $user->fresh()->balance);
        $this->assertSame(1, $user->walletTransactions()->where('type', WalletTransactionType::Deposit)->count());
    }

    public function test_admin_can_reject_a_manual_topup_with_a_reason_without_crediting_balance(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::EWallet,
            'amount' => 50_000,
        ]);
        $proof = $this->submittedProof($deposit);
        $reason = 'Transfer belum masuk ke rekening tujuan.';

        $this->actingAs($admin)
            ->post(route('admin.topup.review', $deposit), ['decision' => 'failed'])
            ->assertSessionHasErrors('reason');

        $this->assertSame(DepositStatus::UnderReview, $deposit->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.topup.review', $deposit), [
                'decision' => 'failed',
                'reason' => $reason,
            ])
            ->assertRedirect(route('admin.topup.show', $deposit));

        $deposit->refresh();
        $this->assertSame(DepositStatus::Failed, $deposit->status);
        $this->assertNotNull($deposit->finalized_at);
        $this->assertSame(DepositProof::ACTION_REJECTED, $proof->fresh()->review_action);
        $this->assertSame($reason, $proof->fresh()->review_reason);
        $this->assertSame(0, $user->fresh()->balance);
        $this->assertSame(0, $user->walletTransactions()->count());
    }

    public function test_proof_download_is_private_to_the_owner_and_authorized_admins(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $user = User::factory()->create();
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'payment_method' => PaymentMethod::EWallet,
        ]);
        $proof = $this->submittedProof($deposit);
        $otherDeposit = Deposit::factory()->create([
            'payment_method' => PaymentMethod::EWallet,
        ]);
        $otherProof = $this->submittedProof($otherDeposit);

        $this->actingAs($user)
            ->get(route('deposits.proof.download', [$deposit, $proof]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=bukti-transfer-'.$deposit->reference.'-'.$proof->getKey().'.png');

        $this->get(route('deposits.proof.download', [$deposit, $otherProof]))->assertNotFound();

        $this->actingAs(User::factory()->create())
            ->get(route('deposits.proof.download', [$deposit, $proof]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.topup.proof.download', [$deposit, $proof]))
            ->assertOk();
    }

    public function test_admin_can_preview_images_inline_but_pdf_remains_download_only(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $deposit = Deposit::factory()->create([
            'payment_method' => PaymentMethod::VirtualAccount,
        ]);
        $imagePath = UploadedFile::fake()->image('transfer.png')->store('deposit-proofs', 'local');
        $imageProof = $deposit->proofs()->create([
            'disk' => 'local',
            'file_path' => $imagePath,
            'submitted_at' => now(),
        ]);
        $pdfPath = UploadedFile::fake()->create('transfer.pdf', 50, 'application/pdf')->store('deposit-proofs', 'local');
        $pdfProof = $deposit->proofs()->create([
            'disk' => 'local',
            'file_path' => $pdfPath,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.topup.proof.preview', [$deposit, $imageProof]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Content-Disposition', 'inline; filename="bukti-transfer-'.$imageProof->getKey().'.png"')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->get(route('admin.topup.proof.preview', [$deposit, $pdfProof]))
            ->assertNotFound();

        $this->get(route('admin.topup.proof.download', [$deposit, $pdfProof]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=bukti-transfer-'.$deposit->reference.'-'.$pdfProof->getKey().'.pdf');
    }

    public function test_admin_topup_page_opens_image_preview_modal_and_keeps_pdf_download_action(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $deposit = Deposit::factory()->create([
            'payment_method' => PaymentMethod::VirtualAccount,
        ]);
        $imagePath = UploadedFile::fake()->image('transfer.png')->store('deposit-proofs', 'local');
        $imageProof = $deposit->proofs()->create([
            'disk' => 'local',
            'file_path' => $imagePath,
            'submitted_at' => now(),
        ]);
        $pdfPath = UploadedFile::fake()->create('transfer.pdf', 50, 'application/pdf')->store('deposit-proofs', 'local');
        $pdfProof = $deposit->proofs()->create([
            'disk' => 'local',
            'file_path' => $pdfPath,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.topup.show', $deposit))
            ->assertOk()
            ->assertSee('data-modal-open="modalDepositProof-'.$imageProof->getKey().'"', false)
            ->assertSee(route('admin.topup.proof.preview', [$deposit, $imageProof]), false)
            ->assertSee('data-proof-zoom-in', false)
            ->assertSee(route('admin.topup.proof.download', [$deposit, $pdfProof]), false)
            ->assertSee('Unduh PDF');
    }

    public function test_proofs_are_deleted_after_90_days_from_finalization_only(): void
    {
        Storage::fake('local');
        $oldDeposit = Deposit::factory()->paid()->create([
            'finalized_at' => now()->subDays(91),
        ]);
        $oldProof = $this->submittedProof($oldDeposit);
        $oldDeposit->update([
            'status' => DepositStatus::Paid,
            'finalized_at' => now()->subDays(91),
        ]);
        $recentDeposit = Deposit::factory()->paid()->create([
            'finalized_at' => now()->subDays(30),
        ]);
        $recentProof = $this->submittedProof($recentDeposit);
        $recentDeposit->update([
            'status' => DepositStatus::Paid,
            'finalized_at' => now()->subDays(30),
        ]);
        $activeDeposit = Deposit::factory()->create([
            'status' => DepositStatus::UnderReview,
            'finalized_at' => null,
        ]);
        $activeProof = $this->submittedProof($activeDeposit);

        $this->artisan('deposits:purge-expired-proofs')->assertSuccessful();

        $this->assertDatabaseMissing('deposit_proofs', ['id' => $oldProof->getKey()]);
        $this->assertDatabaseHas('deposit_proofs', ['id' => $recentProof->getKey()]);
        $this->assertDatabaseHas('deposit_proofs', ['id' => $activeProof->getKey()]);
        Storage::disk('local')->assertMissing($oldProof->file_path);
        Storage::disk('local')->assertExists($recentProof->file_path);
        Storage::disk('local')->assertExists($activeProof->file_path);
    }
}
