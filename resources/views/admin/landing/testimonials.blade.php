@extends('layouts.admin')

@section('title', 'Testimoni Landing Page - Admin Atlantic Pedia')
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
            <h1 class="fw-bold fs-4 m-0">Testimoni</h1>
            <p class="tx-mute fs-12 m-0">Ulasan pelanggan yang tampil pada landing page.</p>
        </div>
        @can('landing.update')
            <button type="button" class="btn-navy" data-modal-open="modalTestimoniTambah">
                <span class="ms ms-md align-middle me-1">add</span>Tambah Testimoni
            </button>
        @endcan
    </div>

    @include('admin.landing._tabs')

    <x-ui.data-table
        id="tblLandingTestimonial"
        title="Daftar Testimoni"
        :columns="[
            ['label' => 'Nama'],
            ['label' => 'Ulasan'],
            ['label' => 'Rating'],
            ['label' => 'Urutan'],
            ['label' => 'Status'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @forelse ($testimonials as $testimonial)
            <tr>
                <td>
                    <div class="fw-semibold">{{ $testimonial->name }}</div>
                    <div class="tx-mute fs-12">{{ $testimonial->role }}</div>
                </td>
                <td class="tx-mute fs-12" style="max-width:26rem">{{ $testimonial->message }}</td>
                <td class="tx-gold fs-12">{{ str_repeat('★', $testimonial->rating) }}</td>
                <td class="tx-mute fs-12 num">{{ $testimonial->sort_order }}</td>
                <td>
                    @if ($testimonial->is_active)
                        <span class="pill bg-mint-l tx-mintt">Aktif</span>
                    @else
                        <span class="pill bg-errc-l tx-err">Nonaktif</span>
                    @endif
                </td>
                <td class="text-end">
                    @can('landing.update')
                        <x-ui.actions :actions="[
                            ['label' => 'Ubah', 'icon' => 'edit', 'modal' => 'modalTestimoniEdit'.$testimonial->getKey()],
                            ['label' => 'Hapus', 'icon' => 'delete', 'tone' => 'tx-err', 'form' => 'del-testimonial-'.$testimonial->getKey(), 'confirm' => 'Hapus testimoni “'.$testimonial->name.'”?', 'confirm_danger' => true],
                        ]" />
                        <form id="del-testimonial-{{ $testimonial->getKey() }}" method="POST" action="{{ route('admin.landing.testimonials.destroy', $testimonial) }}" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center tx-mute py-4">Belum ada testimoni.</td></tr>
        @endforelse
    </x-ui.data-table>
</div>

@can('landing.update')
    @php($ratingOptions = ['5' => '★★★★★', '4' => '★★★★', '3' => '★★★', '2' => '★★', '1' => '★'])

    <x-ui.modal id="modalTestimoniTambah" title="Tambah Testimoni">
        <form action="{{ route('admin.landing.testimonials.store') }}" method="POST" id="formTestimoniTambah">
            @csrf
            <x-ui.input name="name" label="Nama" placeholder="Rizky Pratama" />
            <x-ui.input name="role" label="Peran / keterangan" placeholder="Pemilik Konter Pulsa" />
            <x-ui.textarea name="message" label="Ulasan" :rows="3" />
            <x-ui.select name="rating" label="Rating" :options="$ratingOptions" value="5" />
            <x-ui.input type="number" name="sort_order" label="Urutan tampil" value="0" />
            <x-ui.switch name="is_active" label="Aktif" :checked="true" class="mt-2" />
        </form>
        <x-slot:footer>
            <button type="button" class="btn-soft" data-modal-close>Batal</button>
            <button type="submit" form="formTestimoniTambah" class="btn-navy">Simpan</button>
        </x-slot:footer>
    </x-ui.modal>

    @foreach ($testimonials as $testimonial)
        <x-ui.modal id="modalTestimoniEdit{{ $testimonial->getKey() }}" title="Ubah Testimoni">
            <form action="{{ route('admin.landing.testimonials.update', $testimonial) }}" method="POST" id="formTestimoniEdit{{ $testimonial->getKey() }}">
                @csrf
                @method('PUT')
                <x-ui.input name="name" :id="'name_testi_'.$testimonial->getKey()" label="Nama" :value="$testimonial->name" />
                <x-ui.input name="role" :id="'role_testi_'.$testimonial->getKey()" label="Peran / keterangan" :value="$testimonial->role" />
                <x-ui.textarea name="message" :id="'msg_testi_'.$testimonial->getKey()" label="Ulasan" :rows="3" :value="$testimonial->message" />
                <x-ui.select name="rating" :id="'rating_testi_'.$testimonial->getKey()" label="Rating" :options="$ratingOptions" :value="(string) $testimonial->rating" />
                <x-ui.input type="number" name="sort_order" :id="'sort_testi_'.$testimonial->getKey()" label="Urutan tampil" :value="$testimonial->sort_order" />
                <x-ui.switch name="is_active" label="Aktif" :checked="$testimonial->is_active" class="mt-2" />
            </form>
            <x-slot:footer>
                <button type="button" class="btn-soft" data-modal-close>Batal</button>
                <button type="submit" form="formTestimoniEdit{{ $testimonial->getKey() }}" class="btn-navy">Simpan</button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
@endcan
@endsection
