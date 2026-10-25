@extends('layouts.admin')

@section('title', 'Laporan Reseller - Admin Atlantic Pedia')
@section('page-title', 'Laporan Reseller')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax data-ajax-url="{{ $listRoute }}" data-persist-key="admin-reseller">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Laporan Reseller</h1>
            <p class="tx-mute fs-12 m-0">Tinjau pengajuan, kelola akses reseller, dan pantau penggunaan API terakhir.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span>
            <span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-errc-l">
            <span class="ms tx-err">error</span>
            <span class="fw-semibold fs-12 tx-err">{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-xl-4 m-0">
        <div class="col"><x-ui.stat-card label="Total Pengajuan" value="{{ number_format($stats['totalResellers'], 0, ',', '.') }}" icon="groups" tone="bg-sky-l tx-navy" /></div>
        <div class="col"><x-ui.stat-card label="Reseller Aktif" value="{{ number_format($stats['activeResellers'], 0, ',', '.') }}" icon="storefront" tone="bg-mint-l tx-mintt" /></div>
        <div class="col"><x-ui.stat-card label="Transaksi via API" value="{{ number_format($stats['apiOrders'], 0, ',', '.') }}" icon="api" tone="bg-tealc-m tx-tealt" /></div>
        <div class="col"><x-ui.stat-card label="Nominal via API" value="Rp {{ number_format($stats['apiAmount'], 0, ',', '.') }}" icon="payments" tone="bg-gold tx-gold" /></div>
    </div>

    <x-ui.data-table
        id="tblReseller"
        title="Daftar Reseller"
        :server="true"
        :action="$listRoute"
        :search-value="request('q')"
        search-placeholder="Cari nama atau email reseller..."
        :filters="[
            ['name' => 'status', 'label' => 'Semua Status', 'options' => ['pending' => 'Menunggu review', 'active' => 'Aktif', 'suspended' => 'Ditangguhkan', 'rejected' => 'Ditolak', 'inactive' => 'Tidak aktif'], 'value' => request('status')],
        ]"
        :columns="[
            ['label' => 'Pengguna'],
            ['label' => 'Website / Kanal'],
            ['label' => 'Kontak / IP API'],
            ['label' => 'Diajukan / Sejak'],
            ['label' => 'Status'],
            ['label' => 'Aktivitas API Terakhir'],
            ['label' => 'Transaksi'],
            ['label' => 'Via API'],
            ['label' => 'Nominal API', 'class' => 'text-end'],
            ['label' => 'API Key'],
            ['label' => 'Tindakan'],
        ]"
    >
        @include('admin.reseller._rows')
        <x-slot:footer>
            @include('admin.reseller._footer')
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection
