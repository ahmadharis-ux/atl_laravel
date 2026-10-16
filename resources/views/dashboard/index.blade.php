@extends('layouts.app')

@section('title', 'Beranda - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">

    <div class="row g-3">
        <div class="col-12 col-lg-8 order-lg-2 d-flex">
            <x-ui.searchbox
                :action="route('products.index')"
                :suggest-url="route('products.lookup', ['type' => 'products'])"
                :clear-url="route('search.history.clear')"
                :history="$searchHistory"
                placeholder="Cari produk..."
                class="flex-fill"
            />
        </div>
        <div class="col-12 col-lg-4 order-lg-1">
            <section class="wallet r-card d-flex align-items-center gap-3 h-100">
                <a href="{{ route('saldo.index') }}" class="wallet-fill" aria-label="Buka detail saldo"></a>

                <div class="wallet-cell">
                    <span class="wallet-label">Saldo</span>
                    <span class="d-flex align-items-center gap-2">
                        <span class="wallet-rp">Rp</span>
                        <span class="num wallet-value" id="saldo">{{ number_format($balance, 0, ',', '.') }}</span>
                        <button id="eye" class="btn-eye" type="button" aria-label="Tampilkan atau sembunyikan saldo">
                            <span class="ms ms-sm align-middle">visibility</span>
                        </button>
                    </span>
                </div>

                <span class="wallet-div" aria-hidden="true"></span>

                <div class="wallet-cell">
                    <span class="wallet-label">Poin Reward</span>
                    <span class="num wallet-value">{{ number_format($points, 0, ',', '.') }}<span class="wallet-sub"> Poin</span></span>
                </div>

                <span class="ms ms-sm ms-auto wallet-arrow" aria-hidden="true">chevron_right</span>
            </section>
        </div>
    </div>

    <section class="promo r-card">
        <div class="glow"></div><span class="ms bgico" aria-hidden="true">bolt</span>
        <div class="promo-in d-flex flex-column justify-content-between gap-3">
            <div class="d-flex align-items-center justify-content-between">
                <span class="pill bg-mint tx-mintd"><span class="ms" style="font-size:14px">bolt</span>FLASH PROMO</span>
                <span class="pill text-white" style="background:rgba(255,255,255,.2);backdrop-filter:blur(8px)"><span class="ms" style="font-size:14px">schedule</span>Sisa 2 Hari</span>
            </div>
            <div style="max-width:24rem">
                <div class="tx-mint fw-bold">Cashback s.d 20%</div>
                <h2 class="fw-bold fs-3 lh-sm my-1">Unlock &amp; Transact PPOB</h2>
                <p class="tx-tealc m-0 d-none d-sm-block">Raih kupon diskon token dan game setiap hari!</p>
            </div>
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-1">
                    <span class="rounded-pill bg-mint" style="width:1.5rem;height:6px"></span>
                    <span class="rounded-circle" style="width:6px;height:6px;background:rgba(255,255,255,.4)"></span>
                    <span class="rounded-circle" style="width:6px;height:6px;background:rgba(255,255,255,.4)"></span>
                </div>
                <a href="#" class="btn-white">Klaim Promo</a>
            </div>
        </div>
    </section>

    {{-- Katalog --}}
    <section>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="fw-bold fs-5 m-0 d-flex align-items-center gap-2"><span class="ms tx-teal d-none d-lg-inline">category</span>Katalog Produk</h2>
            <a href="{{ route('products.index', ['tab' => 'brand']) }}" class="fw-semibold tx-teal d-inline-flex align-items-center">Lihat Semua<span class="ms ms-sm">chevron_right</span></a>
        </div>
        <div class="row row-cols-3 row-cols-lg-6 g-2 g-sm-3">
            @foreach ($catalog as $c)
                <div class="col">
                    <a href="{{ $c['url'] }}" class="cat">
                        <span class="ibox ibox-48 cat-ic mb-2"><span class="ms">{{ $c['icon'] }}</span></span>
                        <b>{{ $c['label'] }}</b>
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Brand --}}
    @if ($brands->isNotEmpty())
        <section>
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="fw-bold fs-5 m-0 d-flex align-items-center gap-2"><span class="ms tx-teal d-none d-lg-inline">brand</span>Brand Populer</h2>
                <a href="{{ route('products.index', ['tab' => 'brand']) }}" class="fw-semibold tx-teal d-inline-flex align-items-center">Lihat Semua<span class="ms ms-sm">chevron_right</span></a>
            </div>
            <div class="row row-cols-3 row-cols-sm-4 row-cols-lg-6 g-2 g-sm-3">
                @foreach ($brands as $b)
                    <div class="col">
                        <a href="{{ route('products.index', ['brand' => $b->id]) }}" class="cat" data-tip="{{ $b->name }}">
                            <span class="ibox ibox-48 cat-ic mb-2">
                                @if ($logo = $b->logoUrl())
                                    <img src="{{ $logo }}" alt="{{ $b->name }}" class="brand-logo-img" loading="lazy">
                                @else
                                    <span class="ms">{{ strtoupper(mb_substr($b->name, 0, 1)) }}</span>
                                @endif
                            </span>
                            <b class="cat-label">{{ $b->name }}</b>
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Sesuai pencarian --}}
    @if ($recommended->isNotEmpty())
        <section>
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="fw-bold fs-5 m-0 d-flex align-items-center gap-2"><span class="ms tx-teal d-none d-lg-inline">manage_search</span>Sesuai Pencarianmu</h2>
                <a href="{{ route('products.index', ['q' => $topQueries[0]]) }}" class="fw-semibold tx-teal d-inline-flex align-items-center">Lihat Semua<span class="ms ms-sm">chevron_right</span></a>
            </div>
            @if ($topQueries !== [])
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach ($topQueries as $term)
                        <a href="{{ route('products.index', ['q' => $term]) }}" class="pill bg-mint-l tx-mintt d-inline-flex align-items-center gap-1">
                            <span class="ms" style="font-size:14px">search</span>{{ $term }}
                        </a>
                    @endforeach
                </div>
            @endif
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 g-3">
                @foreach ($recommended as $p)
                    @include('user.product._card', ['p' => $p])
                @endforeach
            </div>
        </section>
    @endif

    {{-- Produk Terlaris --}}
    <section>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="fw-bold fs-5 m-0 d-flex align-items-center gap-2"><span class="ms tx-teal d-none d-lg-inline">local_fire_department</span>Produk Terlaris</h2>
            <a href="{{ route('products.index') }}" class="fw-semibold tx-teal d-inline-flex align-items-center">Lihat Semua<span class="ms ms-sm">chevron_right</span></a>
        </div>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 g-3">
            @forelse ($bestSellers as $p)
                @include('user.product._card', ['p' => $p])
            @empty
                <div class="col-12">
                    <x-ui.empty-state icon="inventory_2" title="Belum ada produk terlaris" description="Produk terlaris akan muncul di sini setelah ada transaksi." />
                </div>
            @endforelse
        </div>
    </section>

    {{-- Info --}}
    <div class="row g-3 g-lg-4">
        <div class="col-md-6">
            <div class="wa h-100">
                <div class="ic"><span>WA</span><span class="ms">chat</span></div>
                <div class="flex-fill" style="min-width:0">
                    <div class="fw-bold">Gabung Channel Resmi</div>
                    <small class="tx-mute fs-12">Update flash sale &amp; gangguan real-time</small>
                </div>
                <a href="#" class="go">Gabung</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info h-100">
                <div class="ic"><span class="ms">devices</span></div>
                <div style="min-width:0">
                    <div class="fw-bold">Atlantic Pedia di Genggaman</div>
                    <small class="fs-12">Kelola &amp; percepat transaksi server PPOB stabil 24/7</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
