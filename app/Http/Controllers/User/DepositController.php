<?php

namespace App\Http\Controllers\User;

use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\MasterBank;
use App\Services\MidtransService;
use App\Support\DepositSettler;
use App\Support\PaymentInstruction;
use App\Support\TransactionLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        try {
            $deposit = Deposit::create([
                'user_id' => $request->user()->getKey(),
                'reference' => $this->generateReference(),
                'amount' => $amount,
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

        return view('user.deposit.show', [
            'deposit' => $deposit,
            'midtransEnabled' => $this->midtrans->isConfigured(),
        ]);
    }

    /**
     * Simulasi konfirmasi pembayaran top up (mode development, belum ada
     * payment gateway). Menambah saldo lewat ledger.
     */
    public function pay(Request $request, Deposit $deposit): RedirectResponse
    {
        abort_unless($deposit->user_id === $request->user()->getKey(), 403);

        if (! $deposit->isPending()) {
            return redirect()->route('deposits.show', $deposit);
        }

        if ($deposit->isExpired()) {
            $deposit->update(['status' => DepositStatus::Expired]);

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
