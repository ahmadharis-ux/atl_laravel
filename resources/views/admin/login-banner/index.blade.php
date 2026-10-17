@extends('layouts.admin')

@section('title', 'Banner Login - Admin Atlantic Pedia')
@section('page-title', 'Banner Login')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax data-ajax-url="{{ $listRoute }}" data-persist-key="admin-login-banner">

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
            <h1 class="fw-bold fs-4 m-0">Banner Login</h1>
            <p class="tx-mute fs-12 m-0">Banner promosi yang muncul sebagai popup tepat setelah pengguna berhasil login.</p>
        </div>
        @can('login-banners.update')
            <button type="button" class="btn-navy" data-modal-open="modalBannerLoginTambah">
                <span class="ms ms-md align-middle me-1">add</span>Tambah Banner
            </button>
        @endcan
    </div>

    <x-ui.data-table
        id="tblLoginBanner"
        title="Daftar Banner Login"
        :server="true"
        :action="$listRoute"
        :search-value="request('q')"
        search-placeholder="Cari judul atau isi banner..."
        :filters="[
            ['name' => 'status', 'label' => 'Semua Status', 'options' => ['active' => 'Aktif', 'inactive' => 'Nonaktif'], 'value' => request('status')],
        ]"
        :columns="[
            ['label' => 'Gambar'],
            ['label' => 'Judul'],
            ['label' => 'Jadwal'],
            ['label' => 'Status'],
            ['label' => 'Urutan'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @include('admin.login-banner._rows')
        <x-slot:footer>
            @include('admin.login-banner._footer')
        </x-slot:footer>
    </x-ui.data-table>

    <div data-ajax-modals>
        @include('admin.login-banner._modals')
    </div>
</div>

@can('login-banners.update')
    <x-ui.modal id="modalBannerLoginTambah" title="Tambah Banner Login" size="lg">
        <form action="{{ route('admin.login-banners.store') }}" method="POST" id="formBannerLoginTambah" enctype="multipart/form-data">
            @csrf
            <x-ui.input name="title" label="Judul" placeholder="Promo Spesial Ramadhan" />
            <x-ui.textarea name="body" label="Isi / Keterangan" :rows="3" hint="Teks singkat yang tampil di dalam popup." />
            <x-ui.input type="file" name="image" label="Gambar" accept="image/png,image/jpeg,image/webp" hint="Opsional. JPG, PNG, atau WEBP. Maksimal 2MB." />
            <x-ui.input type="url" name="link" label="Tautan (opsional)" placeholder="https://..." />
            <x-ui.input name="cta_label" label="Label Tombol (opsional)" value="Selengkapnya" />
            <div class="row g-2">
                <div class="col-md-6"><x-ui.input type="date" name="starts_at" label="Mulai (opsional)" /></div>
                <div class="col-md-6"><x-ui.input type="date" name="ends_at" label="Berakhir (opsional)" /></div>
            </div>
            <x-ui.input type="number" name="sort_order" label="Urutan tampil" value="0" hint="Angka kecil tampil lebih dulu." />
            <x-ui.switch name="is_active" label="Aktif" :checked="true" class="mt-2" />
        </form>
        <x-slot:footer>
            <button type="button" class="btn-soft" data-modal-close>Batal</button>
            <button type="submit" form="formBannerLoginTambah" class="btn-navy">Simpan</button>
        </x-slot:footer>
    </x-ui.modal>
@endcan
@endsection
