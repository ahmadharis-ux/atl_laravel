@extends('layouts.admin')

@section('title', 'Master Bank - Admin Atlantic Pedia')
@section('page-title', 'Master Bank')

@section('content')
<div class="d-flex flex-column gap-4">

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span>
            <span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-errc-l">
            <span class="ms tx-err">error</span>
            <span class="fw-semibold fs-12 tx-err">{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Master Bank</h1>
            <p class="tx-mute fs-12 m-0">Daftar rekening bank tujuan deposit yang tampil ke pengguna.</p>
        </div>
        @can('master-banks.update')
            <button type="button" class="btn-navy" data-modal-open="modalBankTambah">
                <span class="ms ms-md align-middle me-1">add</span>Tambah Bank
            </button>
        @endcan
    </div>

    <x-ui.data-table
        id="tblMasterBank"
        title="Daftar Bank"
        search-placeholder="Cari bank atau nomor rekening..."
        :columns="[
            ['label' => 'Bank'],
            ['label' => 'Nomor Rekening'],
            ['label' => 'Atas Nama'],
            ['label' => 'Status'],
            ['label' => 'Urutan'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
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
                <td class="fw-semibold">{{ $bank->bank_name }}</td>
                <td class="num">{{ $bank->account_number }}</td>
                <td class="tx-mute fs-12">{{ $bank->account_holder }}</td>
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
                <td colspan="6">
                    <x-ui.empty-state icon="account_balance" title="Belum ada master bank"
                        description="Tambahkan rekening bank tujuan deposit agar pengguna dapat melakukan transfer." />
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>
</div>

@can('master-banks.update')
    <x-ui.modal id="modalBankTambah" title="Tambah Master Bank" size="sm">
        <form action="{{ route('admin.master-banks.store') }}" method="POST" id="formBankTambah">
            @csrf
            <x-ui.search-select name="bank_name" label="Nama Bank atau E-Wallet" placeholder="Pilih bank atau e-wallet..."
                :options="\App\Models\MasterBank::nameOptions()" />
            <x-ui.input name="account_number" label="Nomor rekening" placeholder="1234567890" />
            <x-ui.input name="account_holder" label="Atas nama" placeholder="PT Atlantic Pedia" />
            <x-ui.input name="sort_order" type="number" label="Urutan tampil" value="0" hint="Angka kecil tampil lebih dulu." />
            <x-ui.switch name="is_active" label="Aktif" :checked="true" class="mt-2" />
        </form>
        <x-slot:footer>
            <button type="button" class="btn-soft" data-modal-close>Batal</button>
            <button type="submit" form="formBankTambah" class="btn-navy">Simpan</button>
        </x-slot:footer>
    </x-ui.modal>

    @foreach ($banks as $bank)
        <x-ui.modal id="modalBankEdit{{ $bank->getKey() }}" title="Ubah Master Bank" size="sm">
            <form action="{{ route('admin.master-banks.update', $bank) }}" method="POST" id="formBankEdit{{ $bank->getKey() }}">
                @csrf
                @method('PUT')
                @php($bankOptions = collect(\App\Models\MasterBank::nameOptions())->put($bank->bank_name, $bank->bank_name)->all())
                <x-ui.search-select name="bank_name" :id="'bank_name_edit_'.$bank->getKey()" label="Nama Bank atau E-Wallet" placeholder="Pilih bank atau e-wallet..."
                    :options="$bankOptions" :value="$bank->bank_name" />
                <x-ui.input name="account_number" label="Nomor rekening" :value="$bank->account_number" />
                <x-ui.input name="account_holder" label="Atas nama" :value="$bank->account_holder" />
                <x-ui.input name="sort_order" type="number" label="Urutan tampil" :value="$bank->sort_order" />
                <x-ui.switch name="is_active" label="Aktif" :checked="$bank->is_active" class="mt-2" />
            </form>
            <x-slot:footer>
                <button type="button" class="btn-soft" data-modal-close>Batal</button>
                <button type="submit" form="formBankEdit{{ $bank->getKey() }}" class="btn-navy">Simpan</button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
@endcan
@endsection
