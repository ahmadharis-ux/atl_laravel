@extends('layouts.app')

@section('title', 'Transaksi Reseller - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax data-ajax-url="{{ route('settings.reseller.store.transactions') }}" data-persist-key="reseller-store-transactions">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.reseller.store') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Transaksi Toko &amp; API</h1>
            <p class="tx-mute fs-12 m-0">Riwayat pesanan API reseller dan pembelian customer dari toko hosted.</p>
        </div>
    </div>

    <x-ui.data-table
        id="tblResellerTransactions"
        title="Daftar Transaksi"
        :server="true"
        :action="route('settings.reseller.store.transactions')"
        :search-value="request('q')"
        search-placeholder="Cari nomor pesanan atau target..."
        :filters="[
            ['name' => 'source', 'label' => 'Semua Sumber', 'options' => ['api' => 'API reseller', 'store_cash' => 'Toko · Tunai', 'store_qris' => 'Toko · QRIS'], 'value' => request('source')],
            ['name' => 'status', 'label' => 'Semua Status', 'options' => ['pending' => 'Menunggu', 'processing' => 'Diproses', 'success' => 'Berhasil', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa'], 'value' => request('status')],
        ]"
        :columns="[
            ['label' => 'Pesanan'],
            ['label' => 'Sumber'],
            ['label' => 'Tujuan'],
            ['label' => 'Nominal', 'class' => 'text-end'],
            ['label' => 'Status'],
            ['label' => 'Tanggal'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @include('user.settings.reseller-transactions._rows')
        <x-slot:footer>
            <span class="fs-12 tx-mute">Menampilkan {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} transaksi</span>
            {{ $orders->links() }}
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection
