@extends('layouts.admin')

@section('title', 'Ubah Produk · '.$product->name.' - Admin Atlantic Pedia')
@section('page-title', 'Produk · '.$product->payment_type->label())

@section('content')
@php
    $rupiah = fn (int|float $n) => 'Rp '.number_format((float) $n, 0, ',', '.');
@endphp

<div class="d-flex flex-column gap-4">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.products.show', $product) }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-5 m-0">Ubah Produk</h1>
            <span class="fs-12 tx-mute">{{ $product->name }} · {{ $product->code }}</span>
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

    <form method="POST" action="{{ route('admin.products.update', $product) }}" class="d-flex flex-column gap-3">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-lg-8">
                <x-ui.card title="Harga & Markup">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="ui-label">Harga Modal</label>
                            <div class="fs-12 fw-semibold">{{ $rupiah($product->cost_price) }}</div>
                            <div class="ui-hint">Harga dari supplier (diperbarui saat sync).</div>
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">Harga Jual Saat Ini</label>
                            <div class="fs-12 fw-bold tx-tealt">{{ $rupiah($product->selling_price) }}</div>
                            <div class="ui-hint">Harga modal + markup. Perubahan tampil setelah disimpan.</div>
                        </div>

                        <div class="col-md-6">
                            <x-ui.select name="markup_type" label="Tipe Markup" :options="$markupTypes" :value="old('markup_type', $product->markup_type?->value)" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input name="markup_value" type="number" step="0.01" min="0" label="Nilai Markup" :value="old('markup_value', (float) $product->markup_value)" hint="Nominal rupiah atau persen, sesuai tipe markup." />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input name="max_price" type="number" min="0" label="Max Price" :value="old('max_price', $product->max_price)" hint="0 = tanpa batas." />
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-lg-4">
                <x-ui.card title="Informasi Produk">
                    @include('admin.product._field', ['label' => 'Kode', 'value' => $product->code])
                    @include('admin.product._field', ['label' => 'Brand', 'value' => $product->brand?->name ?? '-'])
                    @include('admin.product._field', ['label' => 'Kategori', 'value' => $product->category?->name ?? '-'])
                    @include('admin.product._field', ['label' => 'Tipe', 'value' => $product->productType?->name ?? '-'])
                    @include('admin.product._field', ['label' => 'Seller', 'value' => $product->seller?->name ?? '-'])
                    @include('admin.product._field', ['label' => 'Stok', 'value' => $product->unlimited_stock ? 'Unlimited' : number_format($product->stock, 0, ',', '.')])
                </x-ui.card>
            </div>
        </div>

        <x-ui.card title="Informasi Tampilan">
            <div class="row g-3">
                <div class="col-md-8">
                    <x-ui.input name="name" label="Nama Produk" :value="old('name', $product->name)" placeholder="Nama produk" />
                </div>
                <div class="col-md-4">
                    <x-ui.input name="sort_order" type="number" label="Urutan" :value="old('sort_order', $product->sort_order)" min="0" />
                </div>
                <div class="col-12">
                    <x-ui.textarea name="description" label="Deskripsi" rows="3" :value="old('description', $product->description)" />
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-center gap-3">
                        <x-ui.switch name="status" label="Aktif" :checked="(bool) old('status', $product->status)" />
                        <span class="fs-12 tx-mute">Nonaktifkan untuk menyembunyikan produk dari daftar jual.</span>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.products.show', $product) }}" class="btn-soft">Batal</a>
            <button type="submit" class="btn-navy">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection