@extends('layouts.admin')

@section('title', 'Layanan Landing Page - Admin Atlantic Pedia')
@section('page-title', 'Landing Page')

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
            <h1 class="fw-bold fs-4 m-0">Layanan</h1>
            <p class="tx-mute fs-12 m-0">Kartu layanan pada bagian “Our Service”.</p>
        </div>
        @can('landing.update')
            <button type="button" class="btn-navy" data-modal-open="modalServiceTambah">
                <span class="ms ms-md align-middle me-1">add</span>Tambah Layanan
            </button>
        @endcan
    </div>

    @include('admin.landing._tabs')

    <x-ui.data-table
        id="tblLandingService"
        title="Daftar Layanan"
        :columns="[
            ['label' => 'Ikon'],
            ['label' => 'Judul'],
            ['label' => 'Deskripsi'],
            ['label' => 'Urutan'],
            ['label' => 'Status'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @forelse ($services as $service)
            <tr>
                <td><span class="ibox ibox-40 bg-tealc-m tx-tealt"><span class="ms">{{ $service->icon }}</span></span></td>
                <td class="fw-semibold">{{ $service->title }}</td>
                <td class="tx-mute fs-12" style="max-width:26rem">{{ $service->description }}</td>
                <td class="tx-mute fs-12 num">{{ $service->sort_order }}</td>
                <td>
                    @if ($service->is_active)
                        <span class="pill bg-mint-l tx-mintt">Aktif</span>
                    @else
                        <span class="pill bg-errc-l tx-err">Nonaktif</span>
                    @endif
                </td>
                <td class="text-end">
                    @can('landing.update')
                        <x-ui.actions :actions="[
                            ['label' => 'Ubah', 'icon' => 'edit', 'modal' => 'modalServiceEdit'.$service->getKey()],
                            ['label' => 'Hapus', 'icon' => 'delete', 'tone' => 'tx-err', 'form' => 'del-service-'.$service->getKey(), 'confirm' => 'Hapus layanan “'.$service->title.'”?', 'confirm_danger' => true],
                        ]" />
                        <form id="del-service-{{ $service->getKey() }}" method="POST" action="{{ route('admin.landing.services.destroy', $service) }}" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center tx-mute py-4">Belum ada layanan.</td></tr>
        @endforelse
    </x-ui.data-table>
</div>

@can('landing.update')
    <x-ui.modal id="modalServiceTambah" title="Tambah Layanan">
        <form action="{{ route('admin.landing.services.store') }}" method="POST" id="formServiceTambah">
            @csrf
            <x-ui.input name="icon" label="Ikon (Material Symbols)" value="bolt" hint="Contoh: bolt, smartphone, sports_esports." />
            <x-ui.input name="title" label="Judul" placeholder="Pulsa & Paket Data" />
            <x-ui.textarea name="description" label="Deskripsi" :rows="3" />
            <x-ui.input type="number" name="sort_order" label="Urutan tampil" value="0" hint="Angka kecil tampil lebih dulu." />
            <x-ui.switch name="is_active" label="Aktif" :checked="true" class="mt-2" />
        </form>
        <x-slot:footer>
            <button type="button" class="btn-soft" data-modal-close>Batal</button>
            <button type="submit" form="formServiceTambah" class="btn-navy">Simpan</button>
        </x-slot:footer>
    </x-ui.modal>

    @foreach ($services as $service)
        <x-ui.modal id="modalServiceEdit{{ $service->getKey() }}" title="Ubah Layanan">
            <form action="{{ route('admin.landing.services.update', $service) }}" method="POST" id="formServiceEdit{{ $service->getKey() }}">
                @csrf
                @method('PUT')
                <x-ui.input name="icon" :id="'icon_service_'.$service->getKey()" label="Ikon (Material Symbols)" :value="$service->icon" />
                <x-ui.input name="title" :id="'title_service_'.$service->getKey()" label="Judul" :value="$service->title" />
                <x-ui.textarea name="description" :id="'desc_service_'.$service->getKey()" label="Deskripsi" :rows="3" :value="$service->description" />
                <x-ui.input type="number" name="sort_order" :id="'sort_service_'.$service->getKey()" label="Urutan tampil" :value="$service->sort_order" />
                <x-ui.switch name="is_active" label="Aktif" :checked="$service->is_active" class="mt-2" />
            </form>
            <x-slot:footer>
                <button type="button" class="btn-soft" data-modal-close>Batal</button>
                <button type="submit" form="formServiceEdit{{ $service->getKey() }}" class="btn-navy">Simpan</button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
@endcan
@endsection
