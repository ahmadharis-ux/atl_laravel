<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\MasterBank;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function index(Request $request): View
    {
        return view('user.settings.banks', [
            'bankAccounts' => $request->user()->bankAccounts()
                ->orderByDesc('is_primary')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => [
                'required',
                'string',
                'max:40',
                Rule::unique('bank_accounts')
                    ->where('user_id', $user->getKey())
                    ->where('bank_name', $request->input('bank_name')),
                $this->rejectMasterBankAccount($request),
            ],
            'account_holder' => ['required', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
        ], [
            'account_number.unique' => 'Nomor rekening sudah digunakan',
        ]);

        DB::transaction(function () use ($user, $data, $request): void {
            $isPrimary = $request->boolean('is_primary');

            if ($isPrimary) {
                $user->bankAccounts()->update(['is_primary' => false]);
            }

            $user->bankAccounts()->create([
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_holder' => $data['account_holder'],
                'is_primary' => $isPrimary,
            ]);
        });

        return back()->with('status', 'Rekening berhasil ditambahkan.');
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $this->authorizeOwner($request, $bankAccount);

        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => [
                'required',
                'string',
                'max:40',
                Rule::unique('bank_accounts')
                    ->where('user_id', $request->user()->getKey())
                    ->where('bank_name', $request->input('bank_name'))
                    ->ignore($bankAccount->getKey()),
                $this->rejectMasterBankAccount($request),
            ],
            'account_holder' => ['required', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
        ], [
            'account_number.unique' => 'Nomor rekening sudah digunakan',
        ]);

        DB::transaction(function () use ($request, $bankAccount, $data): void {
            $isPrimary = $request->boolean('is_primary');

            if ($isPrimary) {
                $request->user()->bankAccounts()->whereKeyNot($bankAccount->getKey())->update(['is_primary' => false]);
            }

            $bankAccount->update([
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_holder' => $data['account_holder'],
                'is_primary' => $isPrimary,
            ]);
        });

        return back()->with('status', 'Rekening berhasil diperbarui.');
    }

    public function destroy(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $this->authorizeOwner($request, $bankAccount);

        $bankAccount->delete();

        return back()->with('status', 'Rekening berhasil dihapus.');
    }

    private function authorizeOwner(Request $request, BankAccount $bankAccount): void
    {
        abort_unless($bankAccount->user_id === $request->user()->getKey(), 403);
    }

    /**
     * Rekening pencairan tidak boleh sama dengan rekening tujuan deposit (master bank).
     *
     * @return Closure(string, mixed, Closure(string): void): void
     */
    private function rejectMasterBankAccount(Request $request): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($request): void {
            $conflict = MasterBank::query()
                ->where('bank_name', $request->input('bank_name'))
                ->where('account_number', $value)
                ->exists();

            if ($conflict) {
                $fail('Nomor rekening sudah digunakan');
            }
        };
    }
}
