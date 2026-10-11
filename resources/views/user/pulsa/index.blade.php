@extends('layouts.app')

@section('title', 'Pulsa & Paket Data - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4"
     data-ajax
     data-ajax-url="{{ route('pulsa.index') }}"
     data-persist-key="user.pulsa">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('dashboard') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Pulsa &amp; Paket Data</h1>
            <p class="tx-mute fs-12 m-0">Masukkan nomor HP, operator dideteksi otomatis, lalu pilih paketnya.</p>
        </div>
    </div>

    @include('user.product._operator')

    <form data-ajax-form class="d-flex flex-column gap-3">
        <input type="hidden" name="brand" value="{{ $brandId }}">

        <div class="d-flex flex-wrap align-items-center gap-2">
            <div data-ajax-tabs>
                @include('user.pulsa._tabs')
            </div>
            <button type="button" class="btn-soft ms-auto" data-modal-open="modalFilter">
                <span class="ms ms-sm align-middle me-1">filter_list</span>Filter
            </button>
        </div>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3" data-ajax-rows>
            @include('user.pulsa._cards')
        </div>

        <div data-ajax-footer>
            @include('user.product._footer')
        </div>

        <x-ui.modal id="modalFilter" title="Filter Paket" size="lg">
            <div class="d-flex flex-column gap-3">
                <x-ui.select name="sort" label="Urutkan" :options="$sortOptions" :value="$sort" />
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
