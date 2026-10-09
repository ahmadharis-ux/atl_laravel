@extends('layouts.admin')

@section('title', 'Customer Topup · Product - Admin Atlantic Pedia')
@section('page-title', 'Customer Topup · Product')

@section('content')
<div class="d-flex flex-column gap-4">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Daftar Produk</h1>
            <p class="tx-mute fs-12 m-0">Produk topup beserta markup & harga jual. Data sementara (hardcode).</p>
        </div>
    </div>

    <x-ui.data-table
        id="tblProduct"
        title="Produk"
        search-placeholder="Cari kode, nama, atau brand..."
        :filters="[
            ['name' => 'category', 'label' => 'Semua Category', 'options' => array_combine($categories, $categories)],
            ['name' => 'brand', 'label' => 'Semua Brand', 'options' => array_combine($brands, $brands)],
            ['name' => 'status', 'label' => 'Semua Status', 'options' => ['Aktif' => 'Aktif', 'Nonaktif' => 'Nonaktif']],
        ]"
        :columns="[
            ['label' => 'No'],
            ['label' => 'Product Code'],
            ['label' => 'Brand'],
            ['label' => 'Product Name'],
            ['label' => 'Product Detail'],
            ['label' => 'Markup', 'class' => 'text-end'],
            ['label' => 'Product Price', 'class' => 'text-end'],
            ['label' => 'Selling Price', 'class' => 'text-end'],
            ['label' => 'Status'],
            ['label' => 'Provider'],
            ['label' => 'Aksi', 'class' => 'text-end'],
        ]"
    >
        @foreach ($products as $i => $p)
            @php
                $actions = [];
                if (auth()->user()->can('products.update')) {
                    $actions[] = ['label' => 'Ubah', 'icon' => 'edit', 'href' => '#'];
                }
                $actions[] = ['label' => 'Detail', 'icon' => 'visibility', 'href' => '#'];
            @endphp
            <tr data-category="{{ $p['category'] }}" data-brand="{{ $p['brand'] }}" data-status="{{ $p['status'] }}">
                <td class="tx-mute fs-12">{{ $i + 1 }}</td>
                <td class="fw-semibold fs-12 text-nowrap">{{ $p['code'] }}</td>
                <td><span class="pill bg-tealc-m tx-tealt">{{ $p['brand'] }}</span></td>
                <td class="fw-semibold">{{ $p['name'] }}</td>
                <td class="tx-mute fs-12"><x-ui.tooltip :text="$p['detail']" truncate="14rem">{{ $p['detail'] }}</x-ui.tooltip></td>
                <td class="text-end fs-12">{{ $p['markup'] }}%</td>
                <td class="text-end tx-mute">Rp {{ number_format($p['product_price'], 0, ',', '.') }}</td>
                <td class="text-end fw-semibold">Rp {{ number_format($p['selling_price'], 0, ',', '.') }}</td>
                <td><span class="pill {{ $statusTones[$p['status']] ?? 'bg-tealc-m tx-tealt' }}">{{ $p['status'] }}</span></td>
                <td class="tx-mute fs-12">{{ $p['provider'] }}</td>
                <td class="text-end">
                    <x-ui.actions :actions="$actions" />
                </td>
            </tr>
        @endforeach

        <x-slot:footer>
            <span class="fs-12 tx-mute">Menampilkan 1–{{ count($products) }} dari {{ count($products) }} produk</span>
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection