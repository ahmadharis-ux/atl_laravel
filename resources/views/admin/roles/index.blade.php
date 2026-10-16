@extends('layouts.admin')

@section('title', 'Role & Permission - Admin Atlantic Pedia')
@section('page-title', 'Role & Permission')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax data-ajax-url="{{ $listRoute }}" data-persist-key="admin-roles">

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

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Role &amp; Permission</h1>
            <p class="tx-mute fs-12 m-0">Atur peran dan izin akses ke area admin.</p>
        </div>
        @can('roles.create')
            <a href="{{ route('admin.roles.create') }}" class="btn-navy">
                <span class="ms ms-md align-middle me-1">add</span>Tambah Peran
            </a>
        @endcan
    </div>

    <x-ui.data-table
        id="tblRoles"
        title="Daftar Peran"
        :server="true"
        :action="$listRoute"
        :search-value="request('q')"
        search-placeholder="Cari peran..."
        :columns="[
            ['label' => 'Peran'],
            ['label' => 'Jenis'],
            ['label' => 'Izin'],
            ['label' => 'Pengguna'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @include('admin.roles._rows')

        <x-slot:footer>
            @include('admin.roles._footer')
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection
