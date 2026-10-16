@extends('layouts.app')

@section('title', $brand->name.' - Produk - Atlantic Pedia Tab')

@section('content')
@php use App\Support\ProductRegion; @endphp
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

    <div data-ajax
         data-ajax-url="{{ route('products.brand', $brand) }}"
         data-persist-key="user.brand.{{ $brand->slug }}">
        <form data-ajax-form class="d-flex flex-column gap-3">
            @if ($regions->isNotEmpty())
                <div class="d-flex flex-wrap gap-2" role="group" aria-label="Filter wilayah">
                    <label class="pill pill-opt {{ $selectedRegion === '' ? 'bg-navy text-white' : 'bg-glass tx-mute' }}">
                        <input type="radio" name="region" value="" @checked($selectedRegion === '')>Semua
                    </label>
                    @foreach ($regions as $region)
                        <label class="pill pill-opt {{ $selectedRegion === $region ? 'bg-navy text-white' : 'bg-glass tx-mute' }}">
                            <input type="radio" name="region" value="{{ $region }}" @checked($selectedRegion === $region)>{{ ProductRegion::label($region) }}
                        </label>
                    @endforeach
                </div>
            @endif

            <div class="d-flex flex-wrap align-items-end gap-2">
                <div style="min-width:9rem">
                    <x-ui.currency-input name="min_price" label="Harga Min" :value="request('min_price')" />
                </div>
                <div style="min-width:9rem">
                    <x-ui.currency-input name="max_price" label="Harga Maks" :value="request('max_price')" />
                </div>
                <button type="submit" class="btn-navy"><span class="ms ms-sm align-middle me-1">tune</span>Terapkan</button>
                <button type="button" class="btn-soft" data-ajax-reset data-tip="Reset filter"><span class="ms ms-sm align-middle">restart_alt</span></button>
            </div>

            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-8 g-2 g-sm-3" data-ajax-rows>
                @include('user.product._brand_rows')
            </div>

            <div class="mt-3" data-ajax-footer>
                @include('user.product._brand_footer')
            </div>
        </form>
    </div>
</div>
@endsection
