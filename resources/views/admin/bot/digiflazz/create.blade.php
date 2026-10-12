@extends('layouts.admin')

@section('title', 'Tambah Konfigurasi Digiflazz - Admin Atlantic Pedia')
@section('page-title', 'Tambah Konfigurasi Digiflazz')

@section('content')
@php
    $defaultScraperConfig = json_encode([
        'description_patterns' => ['/100%/i', '/detikan/i'],
        'pattern_mode' => 'AND',
        'prioritize_favorite' => true,
        'filter_multi' => null,
        'filter_min_rating' => null,
        'filter_min_rating_qty' => null,
        'strict_filters' => false,
        'strict_patterns' => false,
        'no_seller_action' => 'skip',
    ], JSON_PRETTY_PRINT);
@endphp

<div class="d-flex flex-column gap-4" data-ajax
     data-ajax-url="{{ route('admin.bot.digiflazz.index') }}"
     data-persist-key="admin.bot.digiflazz">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Tambah Konfigurasi Digiflazz</h1>
            <p class="tx-mute fs-12 m-0">Masukkan cookies & CSRF token dari browser Digiflazz.</p>
        </div>
        <a href="{{ route('admin.bot.digiflazz.index') }}" class="btn-soft">
            <span class="ms ms-sm align-middle me-1">arrow_back</span>Kembali
        </a>
    </div>

    <x-ui.card>
        <form method="POST" action="{{ route('admin.bot.digiflazz.store') }}" data-ajax-form>
            @csrf

            <div class="row g-3">
                <div class="col-12">
                    <x-ui.input name="name" label="Nama Konfigurasi" placeholder="Contoh: Produksi / Staging" :value="old('name', 'default')" required />
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Cookies Content <span class="tx-err">*</span></label>
                    <textarea name="cookies_content" class="form-control font-monospace fs-11" rows="6" placeholder="Paste isi cookies.txt di sini..." required>{{ old('cookies_content') }}</textarea>
                    <div class="form-text">Copy seluruh isi file <code>cookies.txt</code> dari folder Digiflazz scraper.</div>
                    @error('cookies_content')
                        <div class="err show">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <x-ui.input name="csrf_token" label="CSRF Token <span class='tx-err'>*</span>" placeholder="x-csrf-token dari auth.json" :value="old('csrf_token')" required />
                </div>

                <div class="col-md-6">
                    <x-ui.input name="socket_id" label="Socket ID" placeholder="x-socket-id dari auth.json (opsional)" :value="old('socket_id')" />
                </div>

                <div class="col-md-6">
                    <x-ui.input name="xsrf_token" label="XSRF Token" placeholder="x-xsrf-token dari auth.json (opsional)" :value="old('xsrf_token')" />
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Set sebagai konfigurasi aktif</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="ui-label mb-1">Filter Sinkronisasi (opsional)</label>
                    <div class="ui-hint">Kosongkan untuk menyinkronkan <strong>semua</strong> kategori/brand/type.</div>
                </div>
                <div class="col-md-4">
                    <x-ui.multi-select name="categories" label="Kategori" placeholder="Semua kategori"
                        :remote-url="route('admin.bot.digiflazz.options', ['type' => 'categories'])" />
                </div>
                <div class="col-md-4">
                    <x-ui.multi-select name="brands" label="Brand" placeholder="Semua brand"
                        :remote-url="route('admin.bot.digiflazz.options', ['type' => 'brands'])" />
                </div>
                <div class="col-md-4">
                    <x-ui.multi-select name="types" label="Type" placeholder="Semua type"
                        :remote-url="route('admin.bot.digiflazz.options', ['type' => 'types'])" />
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Scraper Config (JSON)</label>
                    <textarea name="scraper_config" class="form-control font-monospace fs-11" rows="8" placeholder='{
  "description_patterns": ["/100%/i", "/detikan/i"],
  "pattern_mode": "AND",
  "prioritize_favorite": true,
  "filter_multi": null,
  "filter_min_rating": null,
  "filter_min_rating_qty": null,
  "strict_filters": false,
  "strict_patterns": false,
  "no_seller_action": "skip"
}'>{{ old('scraper_config', $defaultScraperConfig) }}</textarea>
                    <div class="form-text">Konfigurasi filter scraper. Lihat <code>produk.php</code> untuk referensi.</div>
                    @error('scraper_config')
                        <div class="err show">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('admin.bot.digiflazz.index') }}" class="btn-soft">Batal</a>
                <button type="submit" class="btn-navy"><span class="ms ms-sm align-middle me-1">save</span>Simpan</button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection