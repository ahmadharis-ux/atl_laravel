@extends('layouts.admin')

@section('title', 'Master Bank - Admin Atlantic Pedia')
@section('page-title', 'Master Bank')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax data-ajax-url="{{ $listRoute }}" data-persist-key="admin-master-bank">

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
        :server="true"
        :action="$listRoute"
        :search-value="request('q')"
        search-placeholder="Cari bank, nomor rekening, atau atas nama..."
        :filters="[
            ['name' => 'type', 'label' => 'Semua Jenis', 'options' => $typeOptions, 'value' => request('type')],
        ]"
        :columns="[
            ['label' => 'Jenis'],
            ['label' => 'Bank'],
            ['label' => 'Nomor Rekening'],
            ['label' => 'Atas Nama'],
            ['label' => 'Status'],
            ['label' => 'Urutan'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @include('admin.master-bank._rows')
        <x-slot:footer>
            @include('admin.master-bank._footer')
        </x-slot:footer>
    </x-ui.data-table>

    <div data-ajax-modals>
        @include('admin.master-bank._modals')
    </div>
</div>

@can('master-banks.update')
    <x-ui.modal id="modalBankTambah" title="Tambah Master Bank" size="sm">
        <form action="{{ route('admin.master-banks.store') }}" method="POST" id="formBankTambah">
            @csrf
            <x-ui.select name="type" label="Jenis" :options="\App\Models\MasterBank::typeOptions()" value="bank" />
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
@endcan
@endsection
