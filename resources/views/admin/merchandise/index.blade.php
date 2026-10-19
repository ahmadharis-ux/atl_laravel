@extends('layouts.admin')

@section('title', 'Merchandise - Admin Atlantic Pedia')
@section('page-title', 'Merchandise')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax data-ajax-url="{{ $listRoute }}" data-persist-key="admin-merchandise">

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
            <h1 class="fw-bold fs-4 m-0">Merchandise</h1>
            <p class="tx-mute fs-12 m-0">Katalog merchandise yang dapat ditukar pengguna dengan poin reward.</p>
        </div>
        @can('merchandise.update')
            <button type="button" class="btn-navy" data-modal-open="modalMerchandiseTambah">
                <span class="ms ms-md align-middle me-1">add</span>Tambah Merchandise
            </button>
        @endcan
    </div>

    <x-ui.data-table
        id="tblMerchandise"
        title="Daftar Merchandise"
        :server="true"
        :action="$listRoute"
        :search-value="request('q')"
        search-placeholder="Cari nama merchandise..."
        :filters="[
            ['name' => 'status', 'label' => 'Semua Status', 'options' => ['active' => 'Aktif', 'inactive' => 'Nonaktif'], 'value' => request('status')],
        ]"
        :columns="[
            ['label' => 'Gambar'],
            ['label' => 'Nama'],
            ['label' => 'Biaya Poin'],
            ['label' => 'Stok'],
            ['label' => 'Status'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @include('admin.merchandise._rows')
        <x-slot:footer>
            @include('admin.merchandise._footer')
        </x-slot:footer>
    </x-ui.data-table>

    <div data-ajax-modals>
        @include('admin.merchandise._modals')
    </div>
</div>

@can('merchandise.update')
    <x-ui.modal id="modalMerchandiseTambah" title="Tambah Merchandise" size="lg">
        <form action="{{ route('admin.merchandise.store') }}" method="POST" id="formMerchandiseTambah" enctype="multipart/form-data">
            @csrf
            <x-ui.input name="name" label="Nama" placeholder="Tumbler Atlantic" />
            <x-ui.textarea name="description" label="Deskripsi" :rows="3" />
            <div class="row g-2">
                <div class="col-md-6"><x-ui.input type="number" name="points_cost" label="Biaya Poin" value="100" min="1" /></div>
                <div class="col-md-6"><x-ui.input type="number" name="stock" label="Stok" value="0" min="0" /></div>
            </div>
            <x-ui.input type="file" name="image" label="Gambar" accept="image/png,image/jpeg,image/webp" hint="Opsional. JPG, PNG, atau WEBP. Maksimal 2MB." />
            <x-ui.switch name="is_active" label="Aktif" :checked="true" class="mt-2" />
        </form>
        <x-slot:footer>
            <button type="button" class="btn-soft" data-modal-close>Batal</button>
            <button type="submit" form="formMerchandiseTambah" class="btn-navy">Simpan</button>
        </x-slot:footer>
    </x-ui.modal>
@endcan
@endsection
