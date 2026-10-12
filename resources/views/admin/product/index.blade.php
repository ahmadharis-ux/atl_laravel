@extends('layouts.admin')

@section('title', 'Produk · '.$paymentType->label().' - Admin Atlantic Pedia')
@section('page-title', 'Produk · '.$paymentType->label())

@section('content')
<div class="d-flex flex-column gap-4" data-ajax
     data-ajax-url="{{ $listRoute }}"
     data-persist-key="admin.products.{{ $paymentType->value }}">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Produk {{ $paymentType->label() }}</h1>
            <p class="tx-mute fs-12 m-0">Struktur produk {{ strtolower($paymentType->label()) }}, harga modal, markup & harga jual.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @can('products.update')
                <a href="{{ route('admin.products.sync-digiflazz.index') }}" class="btn-soft" data-tip="Kelola sinkronisasi Digiflazz">
                    <span class="ms ms-sm align-middle me-1">sync</span>Sync Digiflazz
                </a>
            @endcan
            <span class="pill {{ $paymentType === \App\Enums\ProductPaymentType::Prabayar ? 'bg-tealc-m tx-tealt' : 'bg-sky-l tx-skyd' }}">
                {{ $paymentType->label() }}
            </span>
        </div>
    </div>

    <x-ui.card>
        <form method="GET" action="{{ $listRoute }}" class="row g-3 align-items-end" data-ajax-form>
            <div class="col-12 col-md-6 col-lg-3">
                <x-ui.search-select
                    name="category"
                    label="Kategori"
                    :remote="true"
                    :remote-url="route('admin.lookup', ['type' => 'categories'])"
                    :value="request('category')"
                    :selected-label="$selectedCategory"
                    placeholder="Semua Kategori"
                />
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <x-ui.search-select
                    name="brand"
                    label="Brand"
                    :remote="true"
                    :remote-url="route('admin.lookup', ['type' => 'brands'])"
                    :value="request('brand')"
                    :selected-label="$selectedBrand"
                    placeholder="Semua Brand"
                />
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <x-ui.select name="status" label="Status" :options="['1' => 'Aktif', '0' => 'Nonaktif']" :value="request('status')" placeholder="Semua Status" />
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <x-ui.input name="q" label="Pencarian" :value="request('q')" placeholder="Kode / nama / SKU" />
            </div>
            <div class="col-12 col-lg-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn-navy flex-fill"><span class="ms ms-sm align-middle me-1">search</span>Cari</button>
                <button type="button" class="btn-soft" data-ajax-reset data-tip="Reset filter"><span class="ms ms-sm align-middle">restart_alt</span></button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.data-table
        id="dt-{{ $paymentType->value }}"
        title="Produk {{ $paymentType->label() }}"
        :search="false"
        :columns="[
            ['label' => 'No'],
            ['label' => 'Product Code'],
            ['label' => 'Brand'],
            ['label' => 'Product Name'],
            ['label' => 'Kategori'],
            ['label' => 'Harga Modal', 'class' => 'text-end'],
            ['label' => 'Markup', 'class' => 'text-end'],
            ['label' => 'Harga Jual', 'class' => 'text-end'],
            ['label' => 'Stok', 'class' => 'text-end'],
            ['label' => 'Status'],
            ['label' => 'Seller'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @include('admin.product._rows')

        <x-slot:footer>
            @include('admin.product._footer')
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection