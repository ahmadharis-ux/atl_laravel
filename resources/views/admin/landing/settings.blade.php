@extends('layouts.admin')

@section('title', 'Landing Page - Admin Atlantic Pedia')
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
            <h1 class="fw-bold fs-4 m-0">Landing Page</h1>
            <p class="tx-mute fs-12 m-0">Atur teks hero, statistik, tentang, dan nomor WhatsApp bantuan.</p>
        </div>
        <a href="{{ route('landing') }}" target="_blank" rel="noopener" class="btn-soft">Lihat Halaman</a>
    </div>

    @include('admin.landing._tabs')

    <form action="{{ route('admin.landing.settings.update') }}" method="POST" class="d-flex flex-column gap-4">
        @csrf
        @method('PUT')

        <x-ui.card title="Hero / Banner Utama">
            <div class="row g-3">
                <div class="col-md-6"><x-ui.input name="hero_badge" label="Label kecil" :value="$setting->hero_badge" placeholder="PPOB & Top Up Terpercaya" /></div>
                <div class="col-md-6"><x-ui.input name="meta_description" label="Meta description" :value="$setting->meta_description" hint="Untuk SEO, maksimal 255 karakter." /></div>
                <div class="col-12"><x-ui.input name="hero_title" label="Judul hero" :value="$setting->hero_title" required /></div>
                <div class="col-12"><x-ui.textarea name="hero_subtitle" label="Subjudul hero" :rows="3" :value="$setting->hero_subtitle" /></div>
                <div class="col-md-6"><x-ui.input name="hero_cta_label" label="Label tombol utama" :value="$setting->hero_cta_label" /></div>
                <div class="col-md-6"><x-ui.input name="hero_cta_url" label="Tautan tombol utama" :value="$setting->hero_cta_url" hint="Contoh: /register atau https://..." /></div>
                <div class="col-md-6"><x-ui.input name="hero_secondary_label" label="Label tombol kedua" :value="$setting->hero_secondary_label" /></div>
                <div class="col-md-6"><x-ui.input name="hero_secondary_url" label="Tautan tombol kedua" :value="$setting->hero_secondary_url" hint="Kosongkan untuk memakai #layanan." /></div>
            </div>
        </x-ui.card>

        <x-ui.card title="Statistik">
            <div class="row g-3">
                <div class="col-6 col-lg-3"><x-ui.input name="stat_customers" label="Pelanggan" :value="$setting->stat_customers" /></div>
                <div class="col-6 col-lg-3"><x-ui.input name="stat_transactions" label="Transaksi" :value="$setting->stat_transactions" /></div>
                <div class="col-6 col-lg-3"><x-ui.input name="stat_products" label="Produk" :value="$setting->stat_products" /></div>
                <div class="col-6 col-lg-3"><x-ui.input name="stat_support" label="Dukungan" :value="$setting->stat_support" /></div>
            </div>
        </x-ui.card>

        <x-ui.card title="Tentang & Kontak">
            <div class="row g-3">
                <div class="col-12"><x-ui.input name="about_title" label="Judul bagian tentang" :value="$setting->about_title" /></div>
                <div class="col-12"><x-ui.textarea name="about_body" label="Isi bagian tentang" :rows="3" :value="$setting->about_body" /></div>
                <div class="col-md-6"><x-ui.input name="whatsapp_number" label="Nomor WhatsApp CS" :value="$setting->whatsapp_number" hint="Format bebas, mis. 0812... atau 62812..." /></div>
            </div>
        </x-ui.card>

        @can('landing.update')
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn-navy">
                    <span class="ms ms-sm align-middle me-1">save</span>Simpan Perubahan
                </button>
            </div>
        @endcan
    </form>
</div>
@endsection
