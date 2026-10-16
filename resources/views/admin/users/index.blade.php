@extends('layouts.admin')

@section('title', 'Pengguna - Admin Atlantic Pedia')
@section('page-title', 'Manajemen Pengguna')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax data-ajax-url="{{ $listRoute }}" data-persist-key="admin-users">

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
            <h1 class="fw-bold fs-4 m-0">Manajemen Pengguna</h1>
            <p class="tx-mute fs-12 m-0">{{ $users->total() }} pengguna terdaftar.</p>
        </div>
        @can('users.create')
            <a href="{{ route('admin.users.create') }}" class="btn-navy">
                <span class="ms ms-md align-middle me-1">person_add</span>Tambah Pengguna
            </a>
        @endcan
    </div>

    <x-ui.data-table
        id="tblUsers"
        title="Daftar Pengguna"
        :server="true"
        :action="$listRoute"
        :search-value="request('q')"
        search-placeholder="Cari nama, email, atau telepon..."
        :filters="[
            ['name' => 'role', 'label' => 'Semua Peran', 'options' => $roleLabels, 'value' => request('role')],
        ]"
        :columns="[
            ['label' => 'Pengguna'],
            ['label' => 'Email'],
            ['label' => 'Telepon'],
            ['label' => 'Peran'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @include('admin.users._rows')
        <x-slot:footer>
            @include('admin.users._footer')
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection
