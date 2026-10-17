<?php

namespace App\Http\Controllers\Admin\CustomerTopup;

use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Support\DepositSettler;
use App\Support\TransactionLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TopupController extends Controller
{
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
            ->paginate(15)
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
        $deposit->load('user');

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
        $validated = $request->validate([
            'received_amount' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
        ]);

        $settled = DepositSettler::settle($deposit, $request->user(), $validated['received_amount'] ?? null);

        return redirect()
            ->route('admin.topup.index')
            ->with('status', $settled
                ? 'Top up '.$deposit->reference.' ditandai lunas dan saldo pengguna ditambahkan.'
                : 'Top up '.$deposit->reference.' sudah lunas atau tidak dapat diproses.');
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

            $locked->update(['status' => DepositStatus::Expired]);

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
