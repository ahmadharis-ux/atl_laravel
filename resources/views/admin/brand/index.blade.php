@extends('layouts.admin')

@section('title', 'Master Brand - Admin Atlantic Pedia')
@section('page-title', 'Master Brand')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax data-ajax-url="{{ $listRoute }}" data-persist-key="admin-master-brand">

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
            <h1 class="fw-bold fs-4 m-0">Master Brand</h1>
            <p class="tx-mute fs-12 m-0">Kelola logo tiap brand yang tampil di katalog produk.</p>
        </div>
    </div>

    <x-ui.data-table
        id="tblMasterBrand"
        title="Daftar Brand"
        :server="true"
        :action="$listRoute"
        :search-value="request('q')"
        search-placeholder="Cari brand..."
        :columns="[
            ['label' => 'Logo'],
            ['label' => 'Nama'],
            ['label' => 'Slug'],
            ['label' => 'Produk'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @include('admin.brand._rows')
        <x-slot:footer>
            @include('admin.brand._footer')
        </x-slot:footer>
    </x-ui.data-table>

    <div data-ajax-modals>
        @include('admin.brand._modals')
    </div>
</div>
@endsection
