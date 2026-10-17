@extends('layouts.app')

@section('title', $brand->name.' - Produk - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('products.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">{{ $brand->name }}</h1>
            <p class="tx-mute fs-12 m-0">Pilih produk {{ $brand->name }} yang kamu butuhkan.</p>
        </div>
    </div>

    @if ($canValidateNickname)
        <x-ui.card title="Cek ID Akun">
            @include('user.product._nickname', [
                'brand' => $brand->slug,
                'requiresZone' => $requiresZone,
                'target' => '',
                'zone' => '',
            ])
        </x-ui.card>
    @endif

    <div class="d-flex flex-column gap-3"
         data-ajax
         data-ajax-url="{{ route('products.brand', $brand) }}"
         data-persist-key="user.brand.{{ $brand->slug }}">

        <x-ui.searchbox
            :action="route('products.brand', $brand)"
            :value="request('q')"
            placeholder="Cari produk..."
            :show-history="false"
            data-ajax-maybe
        />

        <form data-ajax-form class="d-flex flex-column gap-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="fs-12 tx-mute fw-semibold">
                    @if ($searching)
                        Hasil untuk “{{ $term }}”
                    @else
                        {{ number_format($products->total(), 0, ',', '.') }} produk
                    @endif
                </span>
                <button type="button" class="btn-soft ms-auto" data-modal-open="modalFilter">
                    <span class="ms ms-sm align-middle me-1">tune</span>Filter
                </button>
            </div>

            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-8 g-2 g-sm-3" data-ajax-rows>
                @include('user.product._brand_rows')
            </div>

            <div class="mt-3" data-ajax-footer>
                @include('user.product._brand_footer')
            </div>

            <x-ui.modal id="modalFilter" title="Filter Produk" size="lg">
                <div class="d-flex flex-column gap-3">
                    @if (! empty($regionOptions))
                        <x-ui.search-select
                            name="region"
                            label="Wilayah"
                            :options="$regionOptions"
                            :value="request('region')"
                            placeholder="Semua Wilayah"
                        />
                    @endif
                    @if ($categoryOptions->isNotEmpty())
                        <x-ui.search-select
                            name="category"
                            label="Kategori"
                            :options="$categoryOptions"
                            :value="request('category')"
                            placeholder="Semua Kategori"
                        />
                    @endif
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
</div>
@endsection
