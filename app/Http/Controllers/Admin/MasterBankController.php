<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterBank;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterBankController extends Controller
{
    public function index(): View
    {
        return view('admin.master-bank.index', [
            'banks' => MasterBank::query()
                ->orderBy('sort_order')
                ->orderBy('bank_name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        MasterBank::create([
            'bank_name' => $data['bank_name'],
            'account_number' => $data['account_number'],
            'account_holder' => $data['account_holder'],
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('status', 'Master bank berhasil ditambahkan.');
    }

    public function update(Request $request, MasterBank $masterBank): RedirectResponse
    {
        $data = $this->validated($request, $masterBank);

        $masterBank->update([
            'bank_name' => $data['bank_name'],
            'account_number' => $data['account_number'],
            'account_holder' => $data['account_holder'],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('status', 'Master bank berhasil diperbarui.');
    }

    public function destroy(MasterBank $masterBank): RedirectResponse
    {
        $masterBank->delete();

        return back()->with('status', 'Master bank berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?MasterBank $masterBank = null): array
    {
        return $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => [
                'required',
                'string',
                'max:40',
                Rule::unique('master_banks')
                    ->where('bank_name', $request->input('bank_name'))
                    ->ignore($masterBank?->getKey()),
            ],
            'account_holder' => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'account_number.unique' => 'Nomor rekening ini sudah ada untuk bank tersebut.',
        ]);
    }
}
