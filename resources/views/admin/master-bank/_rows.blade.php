@forelse ($banks as $bank)
    @php
        $rowActions = [];
        if (auth()->user()->can('master-banks.update')) {
            $rowActions[] = ['label' => 'Ubah', 'icon' => 'edit', 'modal' => 'modalBankEdit'.$bank->getKey()];
            $rowActions[] = [
                'label' => 'Hapus',
                'icon' => 'delete',
                'tone' => 'tx-err',
                'confirm' => 'Hapus bank "'.$bank->bank_name.'"?',
                'confirm_danger' => true,
                'form' => 'del-bank-'.$bank->getKey(),
            ];
        }
    @endphp
    <tr>
        <td>
            @if ($bank->type === \App\Models\MasterBank::TYPE_EWALLET)
                <span class="pill bg-skyl tx-skyd">E-Wallet</span>
            @else
                <span class="pill bg-glass tx-mute">Bank</span>
            @endif
        </td>
        <td class="fw-semibold">{{ $bank->bank_name }}</td>
        <td class="num">{{ $bank->account_number }}</td>
        <td class="tx-mute fs-12">{{ $bank->account_holder }}</td>
        <td class="text-end num">
            @if ($bank->admin_fee > 0)
                Rp {{ number_format($bank->admin_fee, 0, ',', '.') }}
            @else
                <span class="tx-mute">&mdash;</span>
            @endif
        </td>
        <td>
            @if ($bank->is_active)
                <span class="pill bg-mint-l tx-mintt">Aktif</span>
            @else
                <span class="pill bg-errc-l tx-err">Nonaktif</span>
            @endif
        </td>
        <td class="tx-mute fs-12 num">{{ $bank->sort_order }}</td>
        <td class="text-end">
            @if (count($rowActions))
                <x-ui.actions :actions="$rowActions" />
            @endif
            @if (auth()->user()->can('master-banks.update'))
                <form id="del-bank-{{ $bank->getKey() }}" method="POST" action="{{ route('admin.master-banks.destroy', $bank) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8">
            <x-ui.empty-state icon="account_balance" title="Belum ada master bank"
                description="Tambahkan rekening bank tujuan deposit agar pengguna dapat melakukan transfer." />
        </td>
    </tr>
@endforelse
