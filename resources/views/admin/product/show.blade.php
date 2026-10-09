@extends('layouts.admin')

@section('title', $product->name.' - Admin Atlantic Pedia')
@section('page-title', 'Produk · '.$product->payment_type->label())

@section('content')
@php
    $rupiah = fn (int|float $n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    $markupLabel = $product->markup_type === \App\Enums\MarkupType::Percent
        ? rtrim(rtrim(number_format((float) $product->markup_value, 2, ',', '.'), '0'), ',').'%'
        : $rupiah($product->markup_value);
    $statusPill = $product->status
        ? '<span class="pill bg-mint-l tx-mintt">Aktif</span>'
        : '<span class="pill bg-errc-l tx-err">Nonaktif</span>';
@endphp

<div class="d-flex flex-column gap-4">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route($product->listRoute()) }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
            <div>
                <h1 class="fw-bold fs-5 m-0">{{ $product->name }}</h1>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="pill bg-tealc-m tx-tealt">{{ $product->code }}</span>
                    <span class="pill {{ $product->payment_type === \App\Enums\ProductPaymentType::Prabayar ? 'bg-tealc-m tx-tealt' : 'bg-sky-l tx-skyd' }}">{{ $product->payment_type->label() }}</span>
                    {!! $statusPill !!}
                </div>
            </div>
        </div>
        @can('products.update')
            <a href="{{ route('admin.products.edit', $product) }}" class="btn-navy">
                <span class="ms ms-sm align-middle me-1">edit</span> Ubah
            </a>
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <x-ui.card title="Harga">
                @include('admin.product._field', ['label' => 'Harga Modal', 'value' => $rupiah($product->cost_price)])
                @include('admin.product._field', ['label' => 'Markup', 'value' => $markupLabel])
                @include('admin.product._field', ['label' => 'Harga Jual', 'value' => $rupiah($product->selling_price)])
                @include('admin.product._field', ['label' => 'Max Price', 'value' => $product->max_price ? $rupiah($product->max_price) : '-'])
            </x-ui.card>
        </div>

        <div class="col-lg-6">
            <x-ui.card title="Klasifikasi">
                @include('admin.product._field', ['label' => 'Brand', 'value' => $product->brand?->name ?? '-'])
                @include('admin.product._field', ['label' => 'Kategori', 'value' => $product->category?->name ?? '-'])
                @include('admin.product._field', ['label' => 'Tipe', 'value' => $product->productType?->name ?? '-'])
                @include('admin.product._field', ['label' => 'Seller', 'value' => $product->seller?->name ?? '-'])
            </x-ui.card>
        </div>

        <div class="col-lg-6">
            <x-ui.card title="Stok & Cut Off">
                @include('admin.product._field', ['label' => 'Stok', 'value' => $product->unlimited_stock ? 'Unlimited' : number_format($product->stock, 0, ',', '.')])
                @include('admin.product._field', ['label' => 'Cut Off', 'value' => ($product->start_cut_off ?? '-').' – '.($product->end_cut_off ?? '-')])
                @include('admin.product._field', ['label' => 'Multi', 'value' => $product->multi ? 'Ya ('.$product->multi_counter.')' : 'Tidak'])
                @include('admin.product._field', ['label' => 'Faktur', 'value' => $product->faktur ? 'Ya' : 'Tidak'])
            </x-ui.card>
        </div>

        <div class="col-lg-6">
            <x-ui.card title="Seller SKU">
                @include('admin.product._field', ['label' => 'SKU Code', 'value' => $product->seller_sku_code ?? '-'])
                @include('admin.product._field', ['label' => 'SKU ID', 'value' => $product->seller_sku_id ?? '-'])
                @include('admin.product._field', ['label' => 'SKU ID (int)', 'value' => $product->seller_sku_id_int ?? '-'])
                @include('admin.product._field', ['label' => 'Koneksi', 'value' => $product->seller_connection_type ?? '-'])
            </x-ui.card>
        </div>

        <div class="col-12">
            <x-ui.card title="Deskripsi">
                @include('admin.product._field', ['label' => 'Deskripsi', 'value' => $product->description ?: '—'])
                @include('admin.product._field', ['label' => 'Deskripsi Seller', 'value' => $product->seller_sku_desc ?: '—'])
            </x-ui.card>
        </div>

        <div class="col-12">
            <x-ui.card title="Metadata">
                <div class="row">
                    <div class="col-sm-6 col-lg-3">@include('admin.product._field', ['label' => 'External ID', 'value' => $product->external_id ?? '-'])</div>
                    <div class="col-sm-6 col-lg-3">@include('admin.product._field', ['label' => 'Product Ref', 'value' => $product->product_ref ?? '-'])</div>
                    <div class="col-sm-6 col-lg-3">@include('admin.product._field', ['label' => 'Sort Order', 'value' => $product->sort_order])</div>
                    <div class="col-sm-6 col-lg-3">@include('admin.product._field', ['label' => 'Status Seller SKU', 'value' => $product->status_seller_sku])</div>
                    <div class="col-sm-6 col-lg-3">@include('admin.product._field', ['label' => 'Last Update', 'value' => $product->last_update?->format('d M Y H:i') ?? '-'])</div>
                    <div class="col-sm-6 col-lg-3">@include('admin.product._field', ['label' => 'Change', 'value' => $product->change ? 'Ya' : 'Tidak'])</div>
                    <div class="col-sm-6 col-lg-3">@include('admin.product._field', ['label' => 'Dibuat', 'value' => $product->created_at?->format('d M Y H:i') ?? '-'])</div>
                    <div class="col-sm-6 col-lg-3">@include('admin.product._field', ['label' => 'Diperbarui', 'value' => $product->updated_at?->format('d M Y H:i') ?? '-'])</div>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection