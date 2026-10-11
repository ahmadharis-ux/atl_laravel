@extends('layouts.admin')

@section('title', 'Dashboard Admin - Atlantic Pedia')
@section('page-title', 'Dashboard')

@section('content')
<div class="d-flex flex-column gap-4">

    <p class="tx-mute fs-12 m-0">Ringkasan data per {{ $dateLong }}.</p>

    {{-- Stat cards --}}
    <div class="row row-cols-2 row-cols-lg-4 g-3">
        @foreach ($stats as $s)
            <div class="col">
                <div class="card-ap r-sm p-3 h-100 d-flex flex-row align-items-center gap-3">
                    <span class="ibox ibox-48 {{ $s['tone'] }}"><span class="ms">{{ $s['icon'] }}</span></span>
                    <div style="min-width:0">
                        <div class="fs-12 tx-mute text-truncate">{{ $s['label'] }}</div>
                        <div class="num fw-800 fs-4 lh-1">{{ is_int($s['value']) ? number_format($s['value'], 0, ',', '.') : $s['value'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Grafik --}}
    <div class="row g-3 g-lg-4">
        <section class="col-lg-8">
            <div class="card-ap r-card p-3 h-100 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between pb-2">
                    <div>
                        <h3 class="fw-bold fs-5 m-0">Tren 7 Hari Terakhir</h3>
                        <p class="fs-12 tx-mute m-0">Jumlah transaksi &amp; pendapatan harian.</p>
                    </div>
                    <a href="{{ route('admin.transactions.index') }}" class="fw-semibold tx-teal">Selengkapnya</a>
                </div>
                <x-ui.chart id="chartTrx" type="line" :config="$transactionChart" height="18rem" />
            </div>
        </section>

        <section class="col-lg-4">
            <div class="card-ap r-card p-3 h-100 d-flex flex-column">
                <div class="pb-2">
                    <h3 class="fw-bold fs-5 m-0">Status Pesanan</h3>
                    <div class="fs-12 tx-mute">30 hari terakhir</div>
                </div>
                @if (count($statusChart['data']['labels']))
                    <x-ui.chart id="chartStatus" type="doughnut" :config="$statusChart" />
                @else
                    <x-ui.empty-state icon="pie_chart" title="Belum ada data" description="Belum ada pesanan dalam 30 hari terakhir." />
                @endif
            </div>
        </section>
    </div>

    <div class="row g-3 g-lg-4">
        {{-- Transaksi terbaru --}}
        <section class="col-lg-7">
            <div class="card-ap r-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between pb-2">
                    <h3 class="fw-bold fs-5 m-0">Transaksi Terbaru</h3>
                    <a href="{{ route('admin.transactions.index') }}" class="fw-semibold tx-teal">Selengkapnya</a>
                </div>
                <ul class="trx list-unstyled m-0">
                    @forelse ($recentOrders as $o)
                        <li>
                            <span class="ibox ibox-44 {{ $o->status->tone() }}"><span class="ms">receipt_long</span></span>
                            <div class="t">
                                <div class="fw-bold text-truncate">{{ $o->product?->name ?? $o->order_number }}</div>
                                <div class="fs-12 tx-mute text-truncate">{{ $o->order_number }} • {{ $o->user?->name ?? 'Pengguna' }}</div>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                                <span class="num fw-bold">Rp {{ number_format($o->amount, 0, ',', '.') }}</span>
                                <span class="pill fs-11 {{ $o->status->tone() }}">{{ $o->status->label() }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="d-block border-0 p-0">
                            <x-ui.empty-state icon="receipt_long" title="Belum ada transaksi" description="Transaksi terbaru akan tampil di sini." />
                        </li>
                    @endforelse
                </ul>
            </div>
        </section>

        {{-- Ringkasan bulan berjalan --}}
        <section class="col-lg-5">
            <div class="balance r-card d-flex flex-column gap-3 h-100">
                <span class="fs-11 fw-bold text-uppercase tx-tealc txt-cap">Pendapatan Bulan Ini</span>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="tx-tealc fw-bold fs-4">Rp</span>
                    <span class="num saldo">{{ number_format($month['revenue'], 0, ',', '.') }}</span>
                </div>
                <span class="fs-12 tx-skyd balance-meta">Periode {{ now()->locale('id')->translatedFormat('F Y') }}</span>
                <div class="row g-2 text-center m-0">
                    <div class="col-4">
                        <div class="num fw-800 fs-5 text-white">{{ number_format($month['orders'], 0, ',', '.') }}</div>
                        <div class="fs-11 tx-skyd">Pesanan</div>
                    </div>
                    <div class="col-4">
                        <div class="num fw-800 fs-5 text-white">{{ number_format($month['success'], 0, ',', '.') }}</div>
                        <div class="fs-11 tx-skyd">Berhasil</div>
                    </div>
                    <div class="col-4">
                        <div class="num fw-800 fs-5 text-white">{{ number_format($month['failed'], 0, ',', '.') }}</div>
                        <div class="fs-11 tx-skyd">Gagal</div>
                    </div>
                </div>
                <div class="d-flex gap-3 mt-auto">
                    <a href="{{ route('admin.transactions.index') }}" class="btn-ap btn-mint flex-fill"><span class="ms">receipt_long</span>Transaksi</a>
                    <a href="{{ route('admin.users.index') }}" class="btn-ap btn-glass flex-fill"><span class="ms">group</span>Pengguna</a>
                </div>
            </div>
        </section>
    </div>

    <div class="row g-3 g-lg-4">
        {{-- Produk terlaris --}}
        <section class="col-lg-7">
            <div class="card-ap r-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between pb-2">
                    <h3 class="fw-bold fs-5 m-0">Produk Terlaris</h3>
                    <a href="{{ route('admin.products.prepay.index') }}" class="fw-semibold tx-teal">Selengkapnya</a>
                </div>
                <ul class="trx list-unstyled m-0">
                    @forelse ($bestSellers as $p)
                        <li>
                            <span class="ibox ibox-44 bg-tealc-m tx-tealt"><span class="ms">inventory_2</span></span>
                            <div class="t">
                                <div class="fw-bold text-truncate">{{ $p->name }}</div>
                                <div class="fs-12 tx-mute text-truncate">{{ $p->brand?->name ?? 'Tanpa brand' }}</div>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                                <span class="num fw-bold">Rp {{ number_format($p->selling_price, 0, ',', '.') }}</span>
                                <span class="pill fs-11 bg-mint-l tx-mintt">{{ number_format($p->sold_count, 0, ',', '.') }} terjual</span>
                            </div>
                        </li>
                    @empty
                        <li class="tx-mute justify-content-center py-4">Belum ada data penjualan.</li>
                    @endforelse
                </ul>
            </div>
        </section>

        {{-- Pengguna terbaru --}}
        <section class="col-lg-5">
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
                                <span class="fs-12 tx-mute">{{ $u->created_at?->locale('id')->translatedFormat('d M Y') }}</span>
                                <span class="pill fs-11 bg-mint-l tx-mintt">Aktif</span>
                            </div>
                        </li>
                    @empty
                        <li class="tx-mute justify-content-center py-4">Belum ada pengguna.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>

    {{-- Aktivitas error --}}
    <section class="card-ap r-card p-3">
        <div class="d-flex align-items-center justify-content-between pb-2">
            <div>
                <h3 class="fw-bold fs-5 m-0">Log Error Terbaru</h3>
                <p class="tx-mute fs-12 m-0">Transaksi yang gagal diproses dan butuh penanganan.</p>
            </div>
        </div>
        @if ($recentErrors->isNotEmpty())
            <div class="table-responsive">
                <table class="table align-middle m-0">
                    <thead>
                        <tr class="fs-12 tx-mute">
                            <th>Level</th>
                            <th>Event</th>
                            <th>Pesan</th>
                            <th>Pesanan</th>
                            <th class="text-end">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentErrors as $log)
                            <tr>
                                <td>
                                    <span class="pill {{ $log->level->tone() }}">
                                        <span class="ms ms-sm align-middle">{{ $log->level->icon() }}</span> {{ $log->level->label() }}
                                    </span>
                                </td>
                                <td class="fw-semibold">{{ $log->event }}</td>
                                <td class="tx-mute text-truncate" style="max-width:22rem">{{ $log->message }}</td>
                                <td>
                                    @if ($log->order)
                                        <a href="{{ route('admin.transactions.show', $log->order) }}" class="fw-semibold tx-teal">{{ $log->order->order_number }}</a>
                                    @else
                                        <span class="tx-mute">-</span>
                                    @endif
                                </td>
                                <td class="text-end tx-mute fs-12">{{ $log->created_at?->locale('id')->translatedFormat('d M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-ui.empty-state icon="verified" title="Tidak ada error" description="Belum ada log error tercatat. Sistem berjalan normal." />
        @endif
    </section>
    </div>
</div>
@endsection
