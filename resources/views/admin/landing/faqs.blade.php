@extends('layouts.admin')

@section('title', 'FAQ Landing Page - Admin Atlantic Pedia')
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
            <h1 class="fw-bold fs-4 m-0">FAQ</h1>
            <p class="tx-mute fs-12 m-0">Pertanyaan yang sering diajukan pada landing page.</p>
        </div>
        @can('landing.update')
            <button type="button" class="btn-navy" data-modal-open="modalFaqTambah">
                <span class="ms ms-md align-middle me-1">add</span>Tambah FAQ
            </button>
        @endcan
    </div>

    @include('admin.landing._tabs')

    <x-ui.data-table
        id="tblLandingFaq"
        title="Daftar FAQ"
        :columns="[
            ['label' => 'Pertanyaan'],
            ['label' => 'Jawaban'],
            ['label' => 'Urutan'],
            ['label' => 'Status'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @forelse ($faqs as $faq)
            <tr>
                <td class="fw-semibold" style="max-width:18rem">{{ $faq->question }}</td>
                <td class="tx-mute fs-12" style="max-width:28rem">{{ \Illuminate\Support\Str::limit($faq->answer, 140) }}</td>
                <td class="tx-mute fs-12 num">{{ $faq->sort_order }}</td>
                <td>
                    @if ($faq->is_active)
                        <span class="pill bg-mint-l tx-mintt">Aktif</span>
                    @else
                        <span class="pill bg-errc-l tx-err">Nonaktif</span>
                    @endif
                </td>
                <td class="text-end">
                    @can('landing.update')
                        <x-ui.actions :actions="[
                            ['label' => 'Ubah', 'icon' => 'edit', 'modal' => 'modalFaqEdit'.$faq->getKey()],
                            ['label' => 'Hapus', 'icon' => 'delete', 'tone' => 'tx-err', 'form' => 'del-faq-'.$faq->getKey(), 'confirm' => 'Hapus FAQ ini?', 'confirm_danger' => true],
                        ]" />
                        <form id="del-faq-{{ $faq->getKey() }}" method="POST" action="{{ route('admin.landing.faqs.destroy', $faq) }}" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center tx-mute py-4">Belum ada FAQ.</td></tr>
        @endforelse
    </x-ui.data-table>
</div>

@can('landing.update')
    <x-ui.modal id="modalFaqTambah" title="Tambah FAQ">
        <form action="{{ route('admin.landing.faqs.store') }}" method="POST" id="formFaqTambah">
            @csrf
            <x-ui.input name="question" label="Pertanyaan" placeholder="Bagaimana cara mulai bertransaksi?" />
            <x-ui.textarea name="answer" label="Jawaban" :rows="4" />
            <x-ui.input type="number" name="sort_order" label="Urutan tampil" value="0" />
            <x-ui.switch name="is_active" label="Aktif" :checked="true" class="mt-2" />
        </form>
        <x-slot:footer>
            <button type="button" class="btn-soft" data-modal-close>Batal</button>
            <button type="submit" form="formFaqTambah" class="btn-navy">Simpan</button>
        </x-slot:footer>
    </x-ui.modal>

    @foreach ($faqs as $faq)
        <x-ui.modal id="modalFaqEdit{{ $faq->getKey() }}" title="Ubah FAQ">
            <form action="{{ route('admin.landing.faqs.update', $faq) }}" method="POST" id="formFaqEdit{{ $faq->getKey() }}">
                @csrf
                @method('PUT')
                <x-ui.input name="question" :id="'q_faq_'.$faq->getKey()" label="Pertanyaan" :value="$faq->question" />
                <x-ui.textarea name="answer" :id="'a_faq_'.$faq->getKey()" label="Jawaban" :rows="4" :value="$faq->answer" />
                <x-ui.input type="number" name="sort_order" :id="'sort_faq_'.$faq->getKey()" label="Urutan tampil" :value="$faq->sort_order" />
                <x-ui.switch name="is_active" label="Aktif" :checked="$faq->is_active" class="mt-2" />
            </form>
            <x-slot:footer>
                <button type="button" class="btn-soft" data-modal-close>Batal</button>
                <button type="submit" form="formFaqEdit{{ $faq->getKey() }}" class="btn-navy">Simpan</button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
@endcan
@endsection
