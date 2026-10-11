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

    <x-ui.card>
        <form method="GET" action="{{ $listRoute }}" class="row g-3 align-items-end" data-ajax-form>
            <div class="col-6 col-md-4 col-lg-3">
                <x-ui.select name="status" label="Status" :options="$statusOptions" :value="request('status')" placeholder="Semua Status" />
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <x-ui.select name="payment_method" label="Metode" :options="$methodOptions" :value="request('payment_method')" placeholder="Semua Metode" />
            </div>
            <div class="col-12 col-md-4 col-lg-4">
                <x-ui.input name="q" label="Pencarian" :value="request('q')" placeholder="Referensi / nama / email pengguna" />
            </div>
            <div class="col-12 col-lg-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn-navy flex-fill"><span class="ms ms-sm align-middle me-1">search</span>Cari</button>
                <button type="button" class="btn-soft" data-ajax-reset><span class="ms ms-sm">restart_alt</span></button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.data-table
        id="tblTopup"
        title="Daftar Topup"
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
