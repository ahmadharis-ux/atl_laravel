@extends('layouts.admin')

@section('title', 'Dashboard Admin - Atlantic Pedia')
@section('page-title', 'Dashboard')

@section('content')
<div class="d-flex flex-column gap-4">

    {{-- Stat cards --}}
    <div class="row row-cols-2 row-cols-lg-4 g-3">
        @foreach ($stats as $s)
            <div class="col">
                <div class="card-ap r-sm p-3 h-100 d-flex flex-row align-items-center gap-3">
                    <span class="ibox ibox-48 {{ $s['tone'] }}"><span class="ms">{{ $s['icon'] }}</span></span>
                    <div style="min-width:0">
                        <div class="fs-12 tx-mute text-truncate">{{ $s['label'] }}</div>
                        <div class="num fw-800 fs-4 lh-1">{{ $value = is_int($s['value']) ? number_format($s['value'], 0, ',', '.') : $s['value'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 g-lg-4">
        {{-- Pengguna terbaru --}}
        <section class="col-lg-7">
            <div class="card-ap r-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between pb-2">
                    <h3 class="fw-bold fs-5 m-0">Pengguna Terbaru</h3>
                    <a href="{{ route('admin.users.index') }}" class="fw-semibold tx-teal">Selengkapnya</a>
                </div>
                <ul class="trx list-unstyled m-0">
                    @forelse ($recentUsers as $u)
                        <li>
                            <span class="ibox ibox-44 bg-tealc-m tx-tealt fw-bold rounded-circle">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                            <div class="t">
                                <div class="fw-bold text-truncate">{{ $u->name }}</div>
                                <div class="fs-12 tx-mute text-truncate">{{ $u->email }}</div>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                                <span class="fs-12 tx-mute">{{ $u->created_at?->format('d M Y') }}</span>
                                <span class="pill fs-11 bg-mint-l tx-mintt">Aktif</span>
                            </div>
                        </li>
                    @empty
                        <li class="tx-mute justify-content-center py-4">Belum ada pengguna.</li>
                    @endforelse
                </ul>
            </div>
        </section>

        {{-- Pintasan admin --}}
        <div class="col-lg-5 d-flex flex-column gap-3">
            <div class="balance r-card d-flex flex-column gap-3">
                <span class="fs-11 fw-bold text-uppercase tx-tealc" style="letter-spacing:.08em">Ringkasan Sistem</span>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="tx-tealc fw-bold fs-4">Rp</span>
                    <span class="num saldo">12.400.000</span>
                </div>
                <span class="fs-12 tx-skyd" style="margin-top:-.5rem;opacity:.8">Total pendapatan bulan berjalan</span>
                <div class="d-flex gap-3">
                    <a href="#" class="btn-ap btn-mint flex-fill"><span class="ms">download</span>Laporan</a>
                    <a href="#" class="btn-ap btn-glass flex-fill"><span class="ms">settings</span>Pengaturan</a>
                </div>
            </div>

            <div class="card-ap r-card p-3">
                <h3 class="fw-bold fs-5 mb-3">Pintasan</h3>
                <div class="row g-2 text-center m-0">
                    @foreach ([['group','Pengguna'],['inventory_2','Produk'],['receipt_long','Transaksi'],['bar_chart','Laporan']] as $s)
                        <a href="#" class="col-3 sc d-flex flex-column align-items-center gap-1 p-1 rounded-3">
                            <span class="ibox ibox-48 bg-tealc-m tx-tealt"><span class="ms">{{ $s[0] }}</span></span>
                            <span class="fs-12 fw-semibold">{{ $s[1] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection