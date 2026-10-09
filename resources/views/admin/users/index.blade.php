@extends('layouts.admin')

@section('title', 'Pengguna - Admin Atlantic Pedia')
@section('page-title', 'Manajemen Pengguna')

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
        search-placeholder="Cari nama, email, atau telepon..."
        :columns="[
            ['label' => 'Pengguna'],
            ['label' => 'Email'],
            ['label' => 'Telepon'],
            ['label' => 'Peran'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
        :filters="[
            ['name' => 'role', 'label' => 'Semua Peran', 'options' => $roleLabels],
        ]"
    >
        @foreach ($users as $user)
            @php
                $role = $user->primaryRole();
                $canEdit = auth()->user()->can('users.update') && \App\Support\Rbac::canManageUser(auth()->user(), $user);
                $canDelete = auth()->user()->can('users.delete')
                    && ! auth()->user()->is($user)
                    && \App\Support\Rbac::canManageUser(auth()->user(), $user);

                $rowActions = [];
                if ($canEdit) {
                    $rowActions[] = ['label' => 'Ubah', 'icon' => 'edit', 'href' => route('admin.users.edit', $user)];
                }
                if ($canDelete) {
                    $rowActions[] = [
                        'label' => 'Hapus',
                        'icon' => 'delete',
                        'tone' => 'tx-err',
                        'confirm' => 'Hapus pengguna "'.$user->name.'"?',
                        'confirm_danger' => true,
                        'form' => 'del-user-'.$user->id,
                    ];
                }
            @endphp
            <tr data-role="{{ $role ?? '' }}">
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="ibox fw-bold rounded-circle" style="width:2rem;height:2rem;background:rgba(159,240,247,.4);color:var(--tealt)">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        <div class="d-flex flex-column">
                            <span class="fw-semibold">{{ $user->name }}</span>
                            @if (auth()->user()->is($user))
                                <span class="fs-11 tx-mute">Anda</span>
                            @endif
                        </div>
                    </div>
                </td>
                <td><x-ui.tooltip :text="$user->email" truncate="11rem" class="tx-mute fs-12">{{ $user->email }}</x-ui.tooltip></td>
                <td class="tx-mute fs-12">{{ $user->phone ?: '—' }}</td>
                <td>
                    <span class="pill {{ \App\Support\Roles::tone($role ?? \App\Support\Roles::USER) }}">{{ $user->roleLabel() }}</span>
                </td>
                <td class="text-end">
                    @if (count($rowActions))
                        <x-ui.actions :actions="$rowActions" />
                    @else
                        <span class="fs-12 tx-mute">—</span>
                    @endif
                    @if ($canDelete)
                        <form id="del-user-{{ $user->id }}" method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach

        <x-slot:footer>
            <span class="fs-12 tx-mute">Menampilkan {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }}</span>
            {{ $users->links() }}
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection