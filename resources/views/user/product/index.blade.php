@extends('layouts.app')

@section('title', 'Produk - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3" data-ajax
     data-ajax-url="{{ route('products.index') }}"
     data-persist-key="user.products"
     data-operator-categories="{{ implode(',', $operatorCategoryIds) }}">

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

    @include('user.product._operator')

    <form data-ajax-form class="d-flex flex-column gap-3">
        <div class="row g-3">
            <div class="col-12 col-xl-9">

                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <div data-ajax-tabs>
                        @include('user.product._tabs')
                    </div>
                    <button type="button" class="btn-soft d-xl-none ms-auto" data-ajax-filter-toggle aria-expanded="false">
                        <span class="ms ms-sm align-middle me-1">tune</span>Filter
                    </button>
                </div>

                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3" data-ajax-rows>
                    @include('user.product._cards')
                </div>

                <div class="mt-3" data-ajax-footer>
                    @include('user.product._footer')
                </div>
            </div>

            <div class="col-12 col-xl-3" data-ajax-filter>
                <div class="card-ap r-card p-3 d-flex flex-column gap-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h2 class="fs-6 fw-bold m-0">Filter</h2>
                        <button type="button" class="btn-soft d-xl-none" data-ajax-filter-toggle aria-expanded="true">
                            <span class="ms ms-sm align-middle">close</span>
                        </button>
                    </div>
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
                        :value="request('brand')"
                        :selected-label="$selectedBrand"
                        placeholder="Semua Brand"
                    />
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-navy flex-fill justify-content-center"><span class="ms ms-sm align-middle me-1">search</span>Cari</button>
                        <button type="button" class="btn-soft" data-ajax-reset data-tip="Reset filter"><span class="ms ms-sm align-middle">restart_alt</span></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection