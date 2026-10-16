@extends('layouts.admin')

@section('title', 'Customer Topup - Admin Atlantic Pedia')
@section('page-title', 'Customer Topup')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax
     data-ajax-url="{{ $listRoute }}"
     data-persist-key="admin.topup">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Transaksi Topup</h1>
            <p class="tx-mute fs-12 m-0">Permintaan top up saldo pelanggan &amp; status pembayarannya.</p>
        </div>
    </div>

    <x-ui.data-table
        id="tblTopup"
        title="Daftar Topup"
        :server="true"
        :action="$listRoute"
        :search-value="request('q')"
        search-placeholder="Referensi / nama / email pengguna"
        :filters="[
            ['name' => 'status', 'label' => 'Semua Status', 'options' => $statusOptions, 'value' => request('status')],
            ['name' => 'payment_method', 'label' => 'Semua Metode', 'options' => $methodOptions, 'value' => request('payment_method')],
        ]"
        :columns="[
            ['label' => 'Tanggal'],
            ['label' => 'Referensi'],
            ['label' => 'Pengguna'],
            ['label' => 'Metode'],
            ['label' => 'Nominal', 'class' => 'text-end'],
            ['label' => 'Status'],
            ['label' => 'Dibayar'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @include('admin.customer-topup._rows')

        <x-slot:footer>
            @include('admin.customer-topup._footer')
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection
