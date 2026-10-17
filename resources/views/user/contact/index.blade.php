@extends('layouts.app')

@section('title', 'Kontak - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3" data-ajax data-ajax-url="{{ $listRoute }}" data-persist-key="user-contact">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('dashboard') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Buku Kontak</h1>
            <p class="tx-mute fs-12 m-0">Simpan daftar kontak agar transaksimu lebih cepat.</p>
        </div>
    </div>

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

    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
        <a href="{{ route('contacts.template') }}" class="btn-soft">
            <span class="ms ms-md align-middle me-1">download</span>Template
        </a>
        <button type="button" class="btn-soft" data-modal-open="modalContactImport">
            <span class="ms ms-md align-middle me-1">upload</span>Import
        </button>
        <button type="button" class="btn-navy" data-modal-open="modalContactTambah">
            <span class="ms ms-md align-middle me-1">add</span>Tambah Kontak
        </button>
    </div>

    <x-ui.data-table
        id="tblKontak"
        title="Daftar Kontak"
        :server="true"
        :action="$listRoute"
        :search-value="request('q')"
        search-placeholder="Cari nama, nomor, atau email..."
        :columns="[
            ['label' => 'Nama'],
            ['label' => 'Nomor'],
            ['label' => 'Email'],
            ['label' => 'Catatan'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @include('user.contact._rows')
        <x-slot:footer>
            @include('user.contact._footer')
        </x-slot:footer>
    </x-ui.data-table>

    <div data-ajax-modals>
        @include('user.contact._modals')
    </div>
</div>

<x-ui.modal id="modalContactTambah" title="Tambah Kontak" size="sm">
    <form action="{{ route('contacts.store') }}" method="POST" id="formContactTambah">
        @csrf
        <x-ui.input name="name" label="Nama" placeholder="Budi Santoso" />
        <x-ui.input name="phone" label="Nomor HP" placeholder="081234567890" />
        <x-ui.input type="email" name="email" label="Email (opsional)" placeholder="budi@example.com" />
        <x-ui.textarea name="notes" label="Catatan (opsional)" :rows="2" />
    </form>
    <x-slot:footer>
        <button type="button" class="btn-soft" data-modal-close>Batal</button>
        <button type="submit" form="formContactTambah" class="btn-navy">Simpan</button>
    </x-slot:footer>
</x-ui.modal>

<x-ui.modal id="modalContactImport" title="Import Kontak" size="sm">
    <form action="{{ route('contacts.import') }}" method="POST" id="formContactImport" enctype="multipart/form-data">
        @csrf
        <p class="tx-mute fs-12">
            Unggah berkas CSV atau Excel dengan kolom: <b>nama</b>, <b>nomor_hp</b>, <b>email</b>, <b>catatan</b>.
            Gunakan tombol <b>Template</b> sebagai contoh.
        </p>
        <x-ui.input type="file" name="file" label="Berkas" accept=".csv,.txt,.xlsx,.xls" hint="Format CSV atau Excel. Maksimal 4MB." />
    </form>
    <x-slot:footer>
        <button type="button" class="btn-soft" data-modal-close>Batal</button>
        <button type="submit" form="formContactImport" class="btn-navy">Import</button>
    </x-slot:footer>
</x-ui.modal>
@endsection
