<?php

namespace App\Http\Controllers\User;

use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\DepositProof;
use App\Models\MasterBank;
use App\Models\User;
use App\Notifications\TransactionResult;
use App\Services\MidtransService;
use App\Support\DepositSettler;
use App\Support\PaymentInstruction;
use App\Support\Roles;
use App\Support\TransactionLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DepositController extends Controller
{
    public const MIN_AMOUNT = 10_000;

    public const MAX_AMOUNT = 10_000_000;

    public const EXPIRY_HOURS = 3;

    public function __construct(private MidtransService $midtrans) {}

    /**
     * Riwayat seluruh permintaan top up milik pengguna (semua status).
     */
    public function index(Request $request): View
    {
        $deposits = $request->user()->deposits()
            ->latest('id')
            ->paginate(15);

        return view('user.deposit.index', [
            'deposits' => $deposits,
        ]);
    }

    public function create(Request $request): View
    {
        $targets = MasterBank::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('bank_name')
            ->get();

        return view('user.deposit.create', [
            'paymentMethods' => $this->topUpMethods(),
            'bankTargets' => $targets->where('type', MasterBank::TYPE_BANK)->values(),
            'ewalletTargets' => $targets->where('type', MasterBank::TYPE_EWALLET)->values(),
            'selectedMethod' => old('payment_method', PaymentMethod::Qris->value),
            'presets' => [20_000, 50_000, 100_000, 200_000, 500_000, 1_000_000],
            'minAmount' => self::MIN_AMOUNT,
            'maxAmount' => self::MAX_AMOUNT,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:'.self::MIN_AMOUNT, 'max:'.self::MAX_AMOUNT],
            'payment_method' => ['required', Rule::in($this->topUpMethodValues())],
            'master_bank_id' => ['nullable', 'integer'],
        ]);

        $method = PaymentMethod::from($validated['payment_method']);
        $masterBank = $this->resolveMasterBank($method, $validated['master_bank_id'] ?? null);
        $amount = (int) $validated['amount'];
        $fee = match ($method) {
            PaymentMethod::Qris => $this->midtrans->qrisFee($amount),
            PaymentMethod::VirtualAccount, PaymentMethod::EWallet => $masterBank?->admin_fee ?? 0,
            PaymentMethod::Saldo => 0,
        };

        try {
            $deposit = Deposit::create([
                'user_id' => $request->user()->getKey(),
                'reference' => $this->generateReference(),
                'amount' => $amount,
                'fee' => $fee,
                'payment_method' => $method,
                'payment_channel' => $masterBank?->bank_name,
                'payment_code' => $masterBank?->account_number ?? PaymentInstruction::code($method),
                'master_bank_id' => $masterBank?->getKey(),
                'status' => DepositStatus::Pending,
                'expires_at' => now()->addHours(self::EXPIRY_HOURS),
            ]);

            if ($method === PaymentMethod::Qris) {
                $this->midtrans->chargeQris($deposit);
            }
        } catch (Throwable $e) {
            TransactionLogger::error('deposit.failed', 'Gagal membuat top up saldo.', $e, [
                'amount' => $amount,
                'payment_method' => $method->value,
            ], null, $request->user());

            throw $e;
        }

        TransactionLogger::info('deposit.created', 'Permintaan top up dibuat.', [
            'reference' => $deposit->reference,
            'amount' => $amount,
            'payment_method' => $method->value,
        ], null, $request->user());

        User::whereHas('roles', fn ($q) => $q->where('name', Roles::ADMIN))
            ->get()
            ->each(fn ($user) => $user->notify(new TransactionResult(
                title: 'Permintaan Top Up Baru',
                body: 'Pengguna '.$deposit->user->name.' ('.$deposit->user->email.') membuat permintaan topup Rp '.number_format($deposit->amount, 0, ',', '.').' - Ref. '.$deposit->reference,
                level: 'info',
                url: route('admin.topup.show', $deposit),
                context: ['type' => 'deposit_new', 'reference' => $deposit->reference],
            )));

        return redirect()
            ->route('deposits.show', $deposit)
            ->with('status', 'Instruksi top up dibuat. Selesaikan pembayaran sebelum kedaluwarsa.');
    }

    /**
     * Ambil master bank tujuan sesuai metode. Wajib untuk VA & e-wallet.
     */
    private function resolveMasterBank(PaymentMethod $method, ?int $id): ?MasterBank
    {
        if (! $method->requiresChannel()) {
            return null;
        }

        $type = $method === PaymentMethod::EWallet ? MasterBank::TYPE_EWALLET : MasterBank::TYPE_BANK;

        $masterBank = MasterBank::query()
            ->where('is_active', true)
            ->where('type', $type)
            ->whereKey($id)
            ->first();

        if ($masterBank === null) {
            throw ValidationException::withMessages([
                'master_bank_id' => $method === PaymentMethod::EWallet
                    ? 'Pilih e-wallet tujuan yang tersedia.'
                    : 'Pilih bank tujuan yang tersedia.',
            ]);
        }

        return $masterBank;
    }

    public function show(Request $request, Deposit $deposit): View|JsonResponse
    {
        abort_unless($deposit->user_id === $request->user()->getKey(), 403);

        if ($request->ajax()) {
            return response()->json([
                'active' => $deposit->isPending(),
                'status' => $deposit->status->value,
            ]);
        }

        $deposit->load(['masterBank', 'proofs']);

        return view('user.deposit.show', [
            'deposit' => $deposit,
        ]);
    }

    public function uploadProof(Request $request, Deposit $deposit): RedirectResponse
    {
        abort_unless($deposit->user_id === $request->user()->getKey(), 403);
        abort_unless(in_array($deposit->payment_method, [PaymentMethod::VirtualAccount, PaymentMethod::EWallet], true), 404);

        $validated = $request->validate([
            'proof' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'extensions:jpg,jpeg,png,webp,pdf',
                File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])->max(5120),
            ],
        ], [
            'proof.required' => 'Pilih bukti transfer yang ingin diunggah.',
            'proof.mimes' => 'Bukti transfer harus berupa JPG, PNG, WEBP, atau PDF.',
            'proof.extensions' => 'Ekstensi berkas harus JPG, PNG, WEBP, atau PDF.',
            'proof.max' => 'Ukuran bukti transfer maksimal 5 MB.',
        ]);

        /** @var UploadedFile $proofFile */
        $proofFile = $validated['proof'];
        $path = $proofFile->store('deposit-proofs', 'local');

        if (! is_string($path)) {
            throw new \RuntimeException('Bukti transfer gagal disimpan ke penyimpanan privat.');
        }

        try {
            $uploaded = DB::transaction(function () use ($deposit, $path): bool {
                $locked = Deposit::query()->whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();

                if ($locked->status === DepositStatus::Pending && $locked->isExpired()) {
                    $locked->update([
                        'status' => DepositStatus::Expired,
                        'finalized_at' => now(),
                    ]);

                    return false;
                }

                if (! in_array($locked->status, [DepositStatus::Pending, DepositStatus::NeedsRevision], true)) {
                    throw ValidationException::withMessages([
                        'proof' => 'Bukti transfer hanya dapat diunggah saat top up menunggu pembayaran atau perbaikan.',
                    ]);
                }

                $locked->proofs()->create([
                    'disk' => 'local',
                    'file_path' => $path,
                    'submitted_at' => now(),
                ]);

                $locked->update([
                    'status' => DepositStatus::UnderReview,
                    'expires_at' => null,
                    'finalized_at' => null,
                ]);

                return true;
            });
        } catch (Throwable $exception) {
            $this->deleteUnattachedProof($path);

            throw $exception;
        }

        if (! $uploaded) {
            $this->deleteUnattachedProof($path);

            return redirect()
                ->route('deposits.show', $deposit)
                ->with('status', 'Batas waktu pembayaran berakhir. Buat top up baru untuk melanjutkan.');
        }

        $deposit->refresh();
        TransactionLogger::info('deposit.proof_submitted', 'Bukti transfer top up dikirim untuk ditinjau.', [
            'reference' => $deposit->reference,
        ], null, $request->user());

        User::permission('topups.view')->get()->each(
            fn (User $admin) => $admin->notify(new TransactionResult(
                title: 'Bukti Top Up Menunggu Verifikasi',
                body: 'Bukti transfer '.$deposit->reference.' telah diunggah oleh '.$request->user()->name.'.',
                level: 'info',
                url: route('admin.topup.show', $deposit),
                context: ['type' => 'deposit_proof_submitted', 'reference' => $deposit->reference],
            )),
        );

        return redirect()
            ->route('deposits.show', $deposit)
            ->with('status', 'Bukti transfer berhasil dikirim. Saldo akan ditambahkan setelah admin memverifikasinya.');
    }

    public function downloadProof(Request $request, Deposit $deposit, DepositProof $proof): StreamedResponse
    {
        abort_unless($deposit->user_id === $request->user()->getKey(), 403);
        abort_unless($proof->deposit_id === $deposit->getKey(), 404);

        return $this->proofDownload($deposit, $proof);
    }

    public function downloadProofForAdmin(Deposit $deposit, DepositProof $proof): StreamedResponse
    {
        abort_unless($proof->deposit_id === $deposit->getKey(), 404);

        return $this->proofDownload($deposit, $proof);
    }

    public function previewProofForAdmin(Deposit $deposit, DepositProof $proof): BinaryFileResponse
    {
        abort_unless($proof->deposit_id === $deposit->getKey(), 404);

        $disk = Storage::disk($proof->disk);
        abort_unless($disk->exists($proof->file_path), 404);

        $mimeType = $disk->mimeType($proof->file_path);
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return response()->file($disk->path($proof->file_path), [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="bukti-transfer-'.$proof->getKey().'.'.pathinfo($proof->file_path, PATHINFO_EXTENSION).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function proofDownload(Deposit $deposit, DepositProof $proof): StreamedResponse
    {
        $disk = Storage::disk($proof->disk);
        abort_unless($disk->exists($proof->file_path), 404);

        $extension = pathinfo($proof->file_path, PATHINFO_EXTENSION);

        return $disk->download(
            $proof->file_path,
            'bukti-transfer-'.$deposit->reference.'-'.$proof->getKey().'.'.$extension,
        );
    }

    private function deleteUnattachedProof(string $path): void
    {
        if (! Storage::disk('local')->delete($path)) {
            Log::error('Failed to remove an unlinked deposit proof upload.', ['path' => $path]);
        }
    }

    /**
     * Simulasi konfirmasi pembayaran top up (mode development, belum ada
     * payment gateway). Menambah saldo lewat ledger.
     */
    public function pay(Request $request, Deposit $deposit): RedirectResponse
    {
        abort_unless($deposit->user_id === $request->user()->getKey(), 403);
        abort_unless($deposit->payment_method === PaymentMethod::Qris, 404);

        if (! $deposit->isPending()) {
            return redirect()->route('deposits.show', $deposit);
        }

        if ($deposit->isExpired()) {
            $deposit->update([
                'status' => DepositStatus::Expired,
                'finalized_at' => now(),
            ]);

            return redirect()
                ->route('deposits.show', $deposit)
                ->with('status', 'Kode pembayaran sudah kedaluwarsa. Silakan buat top up baru.');
        }

        try {
            DepositSettler::settle($deposit, $request->user());
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            TransactionLogger::error('deposit.failed', 'Konfirmasi top up gagal.', $e, [
                'reference' => $deposit->reference,
            ], null, $request->user());

            throw $e;
        }

        return redirect()
            ->route('deposits.show', $deposit)
            ->with('status', 'Top up '.$deposit->reference.' berhasil. Saldo kamu sudah bertambah.');
    }

    /**
     * Tarik status pembayaran QRIS terkini dari Midtrans dan terapkan ke deposit.
     * Dipakai tombol "Cek Status" di halaman top up.
     */
    public function sync(Request $request, Deposit $deposit): RedirectResponse
    {
        abort_unless($deposit->user_id === $request->user()->getKey(), 403);

        if ($deposit->payment_method !== PaymentMethod::Qris) {
            return redirect()->route('deposits.show', $deposit);
        }

        try {
            $status = $this->midtrans->syncDepositStatus($deposit);
        } catch (Throwable $e) {
            TransactionLogger::error('deposit.failed', 'Sinkronisasi status Midtrans gagal.', $e, [
                'reference' => $deposit->reference,
            ], null, $request->user());

            return redirect()
                ->route('deposits.show', $deposit)
                ->with('status', 'Gagal mengambil status dari Midtrans.');
        }

        $message = match ($status) {
            null => 'Status tidak dapat diambil dari Midtrans.',
            'settlement', 'capture' => 'Pembayaran '.$deposit->reference.' terverifikasi. Saldo kamu sudah bertambah.',
            'expire' => 'Kode QRIS top up '.$deposit->reference.' sudah kedaluwarsa.',
            'deny', 'cancel', 'failure' => 'Pembayaran top up '.$deposit->reference.' ditolak atau dibatalkan.',
            default => 'Status pembayaran '.$deposit->reference.': '.$status.'.',
        };

        return redirect()->route('deposits.show', $deposit)->with('status', $message);
    }

    /**
     * Unduh gambar QRIS deposit sebagai berkas agar dapat disimpan pengguna.
     */
    public function downloadQris(Request $request, Deposit $deposit): Response
    {
        abort_unless($deposit->user_id === $request->user()->getKey(), 403);

        $url = data_get($deposit->metadata, 'midtrans.qr_url');
        $image = $this->midtrans->fetchQrImage(is_string($url) ? $url : null);

        abort_if($image === null, 404, 'Gambar QRIS tidak tersedia.');

        return response($image, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="qris-'.$deposit->reference.'.png"',
        ]);
    }

    /**
     * Metode pembayaran untuk top up (semua kecuali saldo).
     *
     * @return list<PaymentMethod>
     */
    private function topUpMethods(): array
    {
        return array_values(array_filter(
            PaymentMethod::cases(),
            fn (PaymentMethod $method): bool => $method !== PaymentMethod::Saldo,
        ));
    }

    /**
     * @return list<string>
     */
    private function topUpMethodValues(): array
    {
        return array_map(fn (PaymentMethod $method): string => $method->value, $this->topUpMethods());
    }

    private function generateReference(): string
    {
        do {
            $reference = 'DEP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Deposit::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
