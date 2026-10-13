@extends('layouts.app')

@section('title', 'Rekening Bank - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Rekening Bank</h1>
            <p class="tx-mute fs-12 m-0">Rekening tujuan pencairan saldo Anda.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span>
            <span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif
    @if ($errors->has('account_number'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-errc-l">
            <span class="ms tx-err">error</span>
            <span class="fw-semibold fs-12 tx-err">{{ $errors->first('account_number') }}</span>
        </div>
    @endif

    <x-ui.card title="Daftar Rekening">
        <x-slot:header>
            <button type="button" class="btn-soft btn-sm" data-modal-open="modalBankTambah">
                <span class="ms ms-sm">add</span>Tambah
            </button>
        </x-slot:header>

        @if ($bankAccounts->isEmpty())
            <x-ui.empty-state icon="account_balance" title="Belum ada rekening"
                description="Tambahkan rekening bank sebagai tujuan pencairan saldo Anda." />
        @else
            <div class="d-flex flex-column gap-2">
                @foreach ($bankAccounts as $bank)
                    <div class="d-flex align-items-center gap-3 p-2 r-sm bg-line">
                        <div class="ibox ibox-44 bg-tealc-m tx-tealt"><span class="ms">account_balance</span></div>
                        <div class="flex-fill">
                            <div class="fw-bold fs-12 d-flex align-items-center gap-1">
                                {{ $bank->bank_name }}
                                @if ($bank->is_primary)<span class="pill bg-mint-l tx-mintt">Utama</span>@endif
                            </div>
                            <div class="fs-12 tx-mute">{{ $bank->account_number }} &middot; {{ $bank->account_holder }}</div>
                        </div>
                        <x-ui.actions :actions="[
                            ['label' => 'Ubah', 'icon' => 'edit', 'modal' => 'modalBankEdit'.$bank->getKey()],
                            ['label' => 'Hapus', 'icon' => 'delete', 'tone' => 'tx-err', 'url' => route('settings.banks.destroy', $bank), 'confirm' => 'Hapus rekening ini?', 'confirm_danger' => true],
                        ]" />
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>
</div>

<x-ui.modal id="modalBankTambah" title="Tambah Rekening" size="sm">
    <form action="{{ route('settings.banks.store') }}" method="POST" id="formBankTambah">
        @csrf
        <x-ui.search-select name="bank_name" label="Nama bank atau e-wallet" placeholder="Pilih bank atau e-wallet..."
            :options="\App\Models\MasterBank::nameOptions()" />
        <x-ui.input name="account_number" label="Nomor rekening" placeholder="1234567890" />
        <x-ui.input name="account_holder" label="Nama pemilik" placeholder="Sesuai buku tabungan" />
        <x-ui.switch name="is_primary" label="Jadikan rekening utama" class="mt-2" />
    </form>
    <x-slot:footer>
        <button type="button" class="btn-soft" data-modal-close>Batal</button>
        <button type="submit" form="formBankTambah" class="btn-navy">Simpan</button>
    </x-slot:footer>
</x-ui.modal>

@foreach ($bankAccounts as $bank)
    <x-ui.modal id="modalBankEdit{{ $bank->getKey() }}" title="Ubah Rekening" size="sm">
        <form action="{{ route('settings.banks.update', $bank) }}" method="POST" id="formBankEdit{{ $bank->getKey() }}">
            @csrf
            @method('PUT')
            @php($bankOptions = collect(\App\Models\MasterBank::nameOptions())->put($bank->bank_name, $bank->bank_name)->all())
            <x-ui.search-select name="bank_name" :id="'bank_name_edit_'.$bank->getKey()" label="Nama bank atau e-wallet"
                placeholder="Pilih bank atau e-wallet..." :options="$bankOptions" :value="$bank->bank_name" />
            <x-ui.input name="account_number" label="Nomor rekening" :value="$bank->account_number" />
            <x-ui.input name="account_holder" label="Nama pemilik" :value="$bank->account_holder" />
            <x-ui.switch name="is_primary" label="Jadikan rekening utama" :checked="$bank->is_primary" />
        </form>
        <x-slot:footer>
            <button type="button" class="btn-soft" data-modal-close>Batal</button>
            <button type="submit" form="formBankEdit{{ $bank->getKey() }}" class="btn-navy">Simpan</button>
        </x-slot:footer>
    </x-ui.modal>
@endforeach
@endsection
