@extends('layouts.admin')

@section('title', 'Role & Permission - Admin Atlantic Pedia')
@section('page-title', 'Role & Permission')

@section('content')
<div class="d-flex flex-column gap-4">

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
        search-placeholder="Cari peran..."
        :columns="[
            ['label' => 'Peran'],
            ['label' => 'Jenis'],
            ['label' => 'Izin'],
            ['label' => 'Pengguna'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @foreach ($roles as $role)
            @php
                $isSystem = in_array($role->name, \App\Support\Roles::all(), true);
                $isSuper = $role->name === \App\Support\Roles::SUPERADMIN;
                $label = $isSystem ? \App\Support\Roles::label($role->name) : ucfirst($role->name);

                $rowActions = [];
                if (auth()->user()->can('roles.update')) {
                    $rowActions[] = ['label' => 'Ubah', 'icon' => 'edit', 'href' => route('admin.roles.edit', $role)];
                }
                if (auth()->user()->can('roles.delete') && ! $isSystem) {
                    $rowActions[] = [
                        'label' => 'Hapus',
                        'icon' => 'delete',
                        'tone' => 'tx-err',
                        'confirm' => 'Hapus peran "'.$label.'"?',
                        'confirm_danger' => true,
                        'form' => 'del-role-'.$role->id,
                    ];
                }
            @endphp
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="pill {{ $isSystem ? \App\Support\Roles::tone($role->name) : 'bg-tealc-m tx-tealt' }}">{{ $label }}</span>
                        <code class="fs-11 tx-mute">{{ $role->name }}</code>
                    </div>
                </td>
                <td>
                    @if ($isSuper)
                        <span class="pill bg-errc-l tx-err">Super</span>
                    @elseif ($isSystem)
                        <span class="pill bg-sky-l tx-navy">Sistem</span>
                    @else
                        <span class="pill bg-mint-l tx-mintt">Kustom</span>
                    @endif
                </td>
                <td class="tx-mute fs-12">{{ $role->permissions_count }} izin</td>
                <td class="tx-mute fs-12">{{ $role->users_count }} pengguna</td>
                <td class="text-end">
                    @if (count($rowActions))
                        <x-ui.actions :actions="$rowActions" />
                    @else
                        <span class="fs-12 tx-mute">—</span>
                    @endif
                    @if (auth()->user()->can('roles.delete') && ! $isSystem)
                        <form id="del-role-{{ $role->id }}" method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>
</div>
@endsection