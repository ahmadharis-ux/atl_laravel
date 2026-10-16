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

    @if ($regions->isNotEmpty())
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('products.brand', $brand) }}"
               class="pill {{ $selectedRegion === '' ? 'bg-navy text-white' : 'bg-glass tx-mute' }} text-decoration-none">
                Semua
            </a>
            @foreach ($regions as $region)
                <a href="{{ route('products.brand', $brand).'?region='.urlencode($region) }}"
                   class="pill {{ $selectedRegion === $region ? 'bg-navy text-white' : 'bg-glass tx-mute' }} text-decoration-none">
                    {{ ProductRegion::label($region) }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-8 g-2 g-sm-3">
        @forelse ($products as $p)
            @include('user.product._card', ['p' => $p, 'href' => route('checkout.show', $p)])
        @empty
            <x-ui.empty-state
                class="empty-state-fill"
                icon="search_off"
                title="Produk tidak ditemukan"
                description="Tidak ada produk dari brand ini yang cocok dengan filter wilayah."
            >
                <a href="{{ route('products.brand', $brand) }}" class="btn-ap btn-navy"><span class="ms ms-sm">restart_alt</span>Lihat Semua Produk</a>
            </x-ui.empty-state>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $products->links() }}
    </div>
</div>
@endsection
