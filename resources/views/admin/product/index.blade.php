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

    <x-ui.data-table
        id="dt-{{ $paymentType->value }}"
        title="Produk {{ $paymentType->label() }}"
        :server="true"
        :action="$listRoute"
        :search-value="request('q')"
        search-placeholder="Kode / nama / SKU"
        :filters="[
            ['name' => 'status', 'label' => 'Semua Status', 'options' => ['1' => 'Aktif', '0' => 'Nonaktif'], 'value' => request('status')],
        ]"
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
        <x-slot:toolbar>
            <div style="min-width:11rem">
                <x-ui.search-select
                    name="category"
                    :remote="true"
                    :remote-url="route('admin.lookup', ['type' => 'categories'])"
                    :value="request('category')"
                    :selected-label="$selectedCategory"
                    placeholder="Semua Kategori"
                />
            </div>
            <div style="min-width:11rem">
                <x-ui.search-select
                    name="brand"
                    :remote="true"
                    :remote-url="route('admin.lookup', ['type' => 'brands'])"
                    :value="request('brand')"
                    :selected-label="$selectedBrand"
                    placeholder="Semua Brand"
                />
            </div>
        </x-slot:toolbar>

        @include('admin.product._rows')

        <x-slot:footer>
            @include('admin.product._footer')
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection