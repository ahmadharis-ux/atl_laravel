@extends('layouts.admin')

@php
    $isEdit = $mode === 'edit';
    $isSelf = $isEdit && auth()->user()->is($user);
@endphp

@section('title', ($isEdit ? 'Ubah Pengguna' : 'Tambah Pengguna').' - Admin Atlantic Pedia')
@section('page-title', $isEdit ? 'Ubah Pengguna' : 'Tambah Pengguna')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center gap-2 mb-3">
            <a href="{{ route('admin.users.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
            <div>
                <h1 class="fw-bold fs-5 m-0">{{ $isEdit ? 'Ubah Pengguna' : 'Tambah Pengguna' }}</h1>
                <span class="fs-12 tx-mute">{{ $isEdit ? $user->name : 'Buat akun untuk admin, staff, CS, atau pengguna.' }}</span>
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

        <x-ui.card>
            <form method="POST" action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}">
                @csrf
                @if ($isEdit) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <x-ui.input name="name" label="Nama Lengkap" :value="old('name', $user->name)" placeholder="Nama pengguna" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.input name="email" type="email" label="Email" :value="old('email', $user->email)" placeholder="nama@email.com" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.input name="phone" label="Telepon" :value="old('phone', $user->phone)" placeholder="08xxxxxxxxxx" />
                    </div>
                    <div class="col-md-6">
                        @php
                            $roleOptions = collect($roles)->mapWithKeys(fn ($r) => [$r => \App\Support\Roles::label($r)])->all();
                        @endphp
                        <x-ui.select
                            name="role"
                            label="Peran"
                            :options="$roleOptions"
                            :value="old('role', $currentRole)"
                            :placeholder="'Pilih peran'"
                            :disabled="$isSelf"
                        />
                        @if ($isSelf)
                            <div class="ui-hint">Anda tidak dapat mengubah peran akun sendiri.</div>
                        @else
                            <div class="ui-hint">Hanya peran dengan otoritas lebih rendah yang tersedia.</div>
                        @endif
                    </div>

                    <div class="col-12"><hr class="my-1"></div>

                    <div class="col-md-6">
                        <x-ui.input name="password" type="password" label="Kata Sandi" :hint="$isEdit ? 'Kosongkan bila tidak ingin mengubah.' : 'Minimal 8 karakter.'" autocomplete="new-password" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.input name="password_confirmation" type="password" label="Konfirmasi Kata Sandi" autocomplete="new-password" />
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.users.index') }}" class="btn-soft">Batal</a>
                    <button type="submit" class="btn-navy">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Pengguna' }}</button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection