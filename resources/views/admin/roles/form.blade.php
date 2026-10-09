@extends('layouts.admin')

@php $isEdit = $mode === 'edit'; @endphp

@section('title', ($isEdit ? 'Ubah Peran' : 'Tambah Peran').' - Admin Atlantic Pedia')
@section('page-title', $isEdit ? 'Ubah Peran' : 'Tambah Peran')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex align-items-center gap-2 mb-3">
            <a href="{{ route('admin.roles.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
            <div>
                <h1 class="fw-bold fs-5 m-0">{{ $isEdit ? 'Ubah Peran' : 'Tambah Peran' }}</h1>
                <span class="fs-12 tx-mute">{{ $isEdit ? $role->name : 'Tentukan nama peran dan izin aksesnya.' }}</span>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger fs-12">
                <ul class="m-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $isEdit ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <x-ui.card class="mb-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        @if ($isSystem)
                            <x-ui.input name="name" label="Nama Peran" :value="$role->name" disabled hint="Nama peran sistem tidak dapat diubah." />
                        @else
                            <x-ui.input name="name" label="Nama Peran" :value="old('name', $role->name)" placeholder="mis. supervisor" hint="Huruf kecil tanpa spasi (a-z, 0-9, _)." />
                        @endif
                    </div>
                    <div class="col-md-6">
                        @if ($locked)
                            <div class="alert alert-warning fs-12 m-0 d-flex align-items-center gap-2">
                                <span class="ms ms-sm">lock</span>
                                Super Admin selalu memiliki seluruh izin dan tidak dapat diubah.
                            </div>
                        @elseif ($isSystem)
                            <div class="alert alert-info fs-12 m-0 d-flex align-items-center gap-2">
                                <span class="ms ms-sm">info</span>
                                Peran sistem: izin bisa disesuaikan, tapi nama tidak.
                            </div>
                        @endif
                    </div>
                </div>
            </x-ui.card>

            @foreach ($groups as $group => $permissions)
                <x-ui.card :title="$group" class="mb-3">
                    <x-slot:header>
                        <span class="pill bg-tealc-m tx-tealt">{{ count($permissions) }} izin</span>
                    </x-slot:header>
                    <div class="row g-2">
                        @foreach ($permissions as $permission => $label)
                            <div class="col-md-6">
                                <x-ui.check-card
                                    name="permissions[]"
                                    :value="$permission"
                                    :label="$label"
                                    :checked="$locked || in_array($permission, $assigned, true)"
                                    :disabled="$locked"
                                />
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @endforeach

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.roles.index') }}" class="btn-soft">Batal</a>
                <button type="submit" class="btn-navy">
                    <span class="ms ms-md align-middle me-1">save</span>{{ $isEdit ? 'Simpan Perubahan' : 'Buat Peran' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection