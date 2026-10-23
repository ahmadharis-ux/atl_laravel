@extends('layouts.admin')

@section('title', 'Tiket & Aduan - Admin Atlantic Pedia')
@section('page-title', 'Tiket & Aduan')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax
     data-ajax-url="{{ $listRoute }}"
     data-persist-key="admin.tickets">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Tiket &amp; Aduan</h1>
            <p class="tx-mute fs-12 m-0">Aduan, saran, dan pengajuan supplier dari pengguna.</p>
        </div>
    </div>

    <x-ui.data-table
        id="dtTiket"
        title="Daftar Tiket"
        :server="true"
        :action="$listRoute"
        :search-value="request('q')"
        search-placeholder="Kode / subjek / pengguna"
        :filters="[
            ['name' => 'status', 'label' => 'Semua Status', 'options' => \App\Enums\TicketStatus::options(), 'value' => request('status')],
            ['name' => 'category', 'label' => 'Semua Jenis', 'options' => \App\Enums\TicketCategory::options(), 'value' => request('category')],
        ]"
        :columns="[
            ['label' => 'Tanggal'],
            ['label' => 'Kode'],
            ['label' => 'Pengguna'],
            ['label' => 'Jenis'],
            ['label' => 'Subjek'],
            ['label' => 'Balasan', 'class' => 'text-center'],
            ['label' => 'Status'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @include('admin.tickets._rows')

        <x-slot:footer>
            @include('admin.tickets._footer')
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection
