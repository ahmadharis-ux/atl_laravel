@extends('layouts.admin')

@section('title', 'Penukaran Poin - Admin Atlantic Pedia')
@section('page-title', 'Penukaran Poin')

@section('content')
<div class="d-flex flex-column gap-4">

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span>
            <span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-errc-l">
            <span class="ms tx-err">error</span>
            <span class="fw-semibold fs-12 tx-err">{{ session('error') }}</span>
        </div>
    @endif

    <div>
        <h1 class="fw-bold fs-4 m-0">Penukaran Poin</h1>
        <p class="tx-mute fs-12 m-0">Penukaran poin reward pengguna ke merchandise.</p>
    </div>

    <x-ui.data-table
        id="tblRedemptions"
        title="Daftar Penukaran"
        :server="true"
        :action="$listRoute"
        :search-value="request('q')"
        search-placeholder="Cari nama pengguna atau merchandise..."
        :filters="[
            ['name' => 'status', 'label' => 'Semua Status', 'options' => ['pending' => 'Menunggu Diproses', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'], 'value' => request('status')],
        ]"
        :columns="[
            ['label' => 'Pengguna'],
            ['label' => 'Merchandise'],
            ['label' => 'Poin'],
            ['label' => 'Waktu'],
            ['label' => 'Status'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @include('admin.redemption._rows')
        <x-slot:footer>
            <span class="fs-12 tx-mute">Menampilkan {{ $redemptions->firstItem() ?? 0 }}–{{ $redemptions->lastItem() ?? 0 }} dari {{ $redemptions->total() }} penukaran</span>
            {{ $redemptions->links() }}
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection
