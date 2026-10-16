@extends('layouts.app')

@section('title', 'Produk - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3" data-ajax
     data-ajax-url="{{ route('products.index') }}"
     data-persist-key="user.products">

    <div>
        <h1 class="fw-bold fs-4 m-0">Produk</h1>
        <p class="tx-mute fs-12 m-0">Pilih produk {{ strtolower($paymentType->label()) }} yang kamu butuhkan.</p>
    </div>

    <x-ui.searchbox
        :action="route('products.index')"
        :suggest-url="route('products.lookup', ['type' => 'products'])"
        :clear-url="route('search.history.clear')"
        :history="$searchHistory"
        :value="request('q')"
        placeholder="Cari produk..."
        data-ajax-maybe
    />

    <form data-ajax-form class="d-flex flex-column gap-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div data-ajax-tabs>
                @include('user.product._tabs')
            </div>
            <button type="button" class="btn-soft ms-auto" data-modal-open="modalFilter">
                <span class="ms ms-sm align-middle me-1">tune</span>Filter
            </button>
        </div>

        @include('user.product._brands')

        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-8 g-2 g-sm-3" data-ajax-rows>
            @include('user.product._cards')
        </div>

        <div class="mt-3" data-ajax-footer>
            @include('user.product._footer')
        </div>

        <x-ui.modal id="modalFilter" title="Filter Produk" size="lg">
            <div class="d-flex flex-column gap-3">
                <x-ui.search-select
                    name="category"
                    label="Kategori"
                    :remote="true"
                    :remote-url="route('products.lookup', ['type' => 'categories'])"
                    :value="request('category')"
                    :selected-label="$selectedCategory"
                    placeholder="Semua Kategori"
                />
                <x-ui.search-select
                    name="brand"
                    label="Brand"
                    :remote="true"
                    :remote-url="route('products.lookup', ['type' => 'brands'])"
                    depends-on="category"
                    :value="request('brand')"
                    :selected-label="$selectedBrand"
                    placeholder="Semua Brand"
                />
                <div class="row g-2">
                    <div class="col-6">
                        <x-ui.currency-input name="min_price" label="Harga Min" :value="request('min_price')" />
                    </div>
                    <div class="col-6">
                        <x-ui.currency-input name="max_price" label="Harga Maks" :value="request('max_price')" />
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn-navy flex-fill justify-content-center"><span class="ms ms-sm align-middle me-1">search</span>Cari</button>
                    <button type="button" class="btn-soft" data-ajax-reset data-tip="Reset filter"><span class="ms ms-sm align-middle">restart_alt</span></button>
                </div>
            </div>
        </x-ui.modal>
    </form>
</div>
@endsection