@extends('layouts.admin')

@section('title', 'Transaksi - Admin Atlantic Pedia')
@section('page-title', 'Transaksi')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax
     data-ajax-url="{{ $listRoute }}"
     data-persist-key="admin.transactions">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Transaksi</h1>
            <p class="tx-mute fs-12 m-0">Daftar pesanan pelanggan beserta status &amp; riwayat prosesnya.</p>
        </div>
    </div>

    <x-ui.data-table
        id="dtTransaksi"
        title="Daftar Transaksi"
        :server="true"
        :action="$listRoute"
        :search-value="request('q')"
        search-placeholder="No. transaksi / tujuan / pengguna"
        :filters="[
            ['name' => 'status', 'label' => 'Semua Status', 'options' => $statusOptions, 'value' => request('status')],
            ['name' => 'payment_method', 'label' => 'Semua Metode', 'options' => $methodOptions, 'value' => request('payment_method')],
        ]"
        :columns="[
            ['label' => 'Tanggal'],
            ['label' => 'No. Transaksi'],
            ['label' => 'Pengguna'],
            ['label' => 'Produk'],
            ['label' => 'Tujuan'],
            ['label' => 'Metode'],
            ['label' => 'Jumlah', 'class' => 'text-end'],
            ['label' => 'Status'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @include('admin.transaction._rows')

        <x-slot:footer>
            @include('admin.transaction._footer')
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection
