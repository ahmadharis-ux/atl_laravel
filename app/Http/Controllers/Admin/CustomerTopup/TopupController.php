<?php

namespace App\Http\Controllers\Admin\CustomerTopup;

use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Admin\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\DepositProof;
use App\Notifications\TransactionResult;
use App\Support\DepositSettler;
use App\Support\TransactionLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TopupController extends Controller
{
    use HasPerPage;

    /**
     * Daftar permintaan top up saldo dari seluruh pengguna.
     */
    public function index(Request $request): View|JsonResponse
    {
        $deposits = Deposit::query()
            ->with('user')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->query('payment_method')))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $request->input('q')).'%';

                $query->where(function ($query) use ($term): void {
                    $query->where('reference', 'like', $term)
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term));
                });
            })
            ->latest('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $data = [
            'deposits' => $deposits,
            'listRoute' => route('admin.topup.index'),
            'statusOptions' => collect(DepositStatus::cases())->mapWithKeys(fn (DepositStatus $s) => [$s->value => $s->label()])->all(),
            'methodOptions' => collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $m) => [$m->value => $m->label()])->all(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('admin.customer-topup._rows', $data)->render(),
                'footer' => view('admin.customer-topup._footer', $data)->render(),
            ]);
        }

        return view('admin.customer-topup.topup', $data);
    }

    public function show(Deposit $deposit): View
    {
        $deposit->load(['user', 'proofs.reviewer']);

        return view('admin.customer-topup.show', [
            'deposit' => $deposit,
            'ledger' => $deposit->walletTransaction,
        ]);
    }

    /**
     * Menandai top up lunas dan menambah saldo pengguna (idempoten).
     */
    public function settle(Request $request, Deposit $deposit): RedirectResponse
    {
        if (in_array($deposit->payment_method, [PaymentMethod::VirtualAccount, PaymentMethod::EWallet], true)
            && in_array($deposit->status, [DepositStatus::Pending, DepositStatus::NeedsRevision], true)) {
            return redirect()
                ->route('admin.topup.show', $deposit)
                ->with('status', 'Top up transfer hanya dapat disetujui setelah bukti pembayaran dikirim dan menunggu verifikasi.');
        }

        $minimumReceived = in_array($deposit->payment_method, [PaymentMethod::VirtualAccount, PaymentMethod::EWallet], true)
            ? $deposit->fee + 1
            : 0;
        $validated = $request->validate([
            'received_amount' => ['nullable', 'integer', 'min:'.$minimumReceived, 'max:1000000000'],
        ]);

        $settled = DepositSettler::settle($deposit, $request->user(), $validated['received_amount'] ?? null);

        return redirect()
            ->route('admin.topup.index')
            ->with('status', $settled
                ? 'Top up '.$deposit->reference.' ditandai lunas dan saldo pengguna ditambahkan.'
                : 'Top up '.$deposit->reference.' sudah lunas atau tidak dapat diproses.');
    }

    public function review(Request $request, Deposit $deposit): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['needs_revision', 'failed'])],
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'decision.required' => 'Pilih tindakan untuk bukti transfer ini.',
            'reason.required' => 'Alasan wajib diisi agar pengguna tahu tindak lanjutnya.',
        ]);

        $reviewed = DB::transaction(function () use ($request, $deposit, $validated): bool {
            $locked = Deposit::query()->whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== DepositStatus::UnderReview) {
                return false;
            }

            $proof = $locked->proofs()->lockForUpdate()->first();

            if ($proof === null || $proof->review_action !== null) {
                return false;
            }

            $isRevision = $validated['decision'] === 'needs_revision';
            $proof->update([
                'reviewed_by' => $request->user()->getKey(),
                'review_action' => $isRevision ? DepositProof::ACTION_NEEDS_REVISION : DepositProof::ACTION_REJECTED,
                'review_reason' => $validated['reason'],
                'reviewed_at' => now(),
            ]);
            $locked->update([
                'status' => $isRevision ? DepositStatus::NeedsRevision : DepositStatus::Failed,
                'finalized_at' => $isRevision ? null : now(),
            ]);

            TransactionLogger::warning(
                $isRevision ? 'deposit.proof_revision_requested' : 'deposit.proof_rejected',
                $isRevision ? 'Admin meminta perbaikan bukti transfer.' : 'Admin menolak bukti transfer.',
                [
                    'reference' => $locked->reference,
                    'reason' => $validated['reason'],
                    'by' => $request->user()->getKey(),
                ],
                null,
                $locked->user,
            );

            return true;
        });

        if (! $reviewed) {
            return redirect()
                ->route('admin.topup.show', $deposit)
                ->with('status', 'Bukti ini sudah ditinjau atau top up tidak lagi menunggu verifikasi.');
        }

        $deposit->refresh();
        $isRevision = $validated['decision'] === 'needs_revision';
        $deposit->user->notify(new TransactionResult(
            title: $isRevision ? 'Bukti Transfer Perlu Diperbaiki' : 'Top Up Ditolak',
            body: $validated['reason'],
            level: $isRevision ? 'warning' : 'error',
            url: route('deposits.show', $deposit),
            context: ['type' => $isRevision ? 'deposit_proof_revision' : 'deposit_proof_rejected', 'reference' => $deposit->reference],
        ));

        return redirect()
            ->route('admin.topup.show', $deposit)
            ->with('status', $isRevision
                ? 'Permintaan perbaikan bukti transfer telah dikirim ke pengguna.'
                : 'Top up ditolak. Saldo pengguna tidak berubah.');
    }

    /**
     * Membatalkan top up yang masih menunggu pembayaran.
     */
    public function expire(Request $request, Deposit $deposit): RedirectResponse
    {
        DB::transaction(function () use ($request, $deposit): void {
            $locked = Deposit::query()->whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== DepositStatus::Pending) {
                return;
            }

            $locked->update([
                'status' => DepositStatus::Expired,
                'finalized_at' => now(),
            ]);

            TransactionLogger::warning('deposit.expired', 'Top up dibatalkan oleh admin.', [
                'reference' => $locked->reference,
                'by' => $request->user()->getKey(),
            ], null, $locked->user);
        });

        return redirect()
            ->route('admin.topup.index')
            ->with('status', 'Top up '.$deposit->reference.' dibatalkan.');
    }
}
