@extends('layouts.admin')

@section('title', 'Manajemen Halaman - Admin Atlantic Pedia')
@section('page-title', 'Manajemen Halaman')

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

    <div>
        <h1 class="fw-bold fs-4 m-0">Manajemen Halaman</h1>
        <p class="tx-mute fs-12 m-0">Aktifkan atau nonaktifkan halaman pengguna. Bila dimatikan, halaman terkait menampilkan "Coming Soon".</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <x-ui.card title="Status Halaman">
                <form action="{{ route('admin.features.update') }}" method="POST" class="d-flex flex-column gap-3">
                    @csrf
                    @method('PUT')

                    @php
                        $features = [
                            ['key' => 'reseller', 'column' => 'reseller_enabled', 'label' => 'Reseller', 'icon' => 'storefront', 'desc' => 'Halaman harga & keanggotaan reseller pengguna.', 'url' => auth()->user()->can('resellers.view') ? route('admin.resellers.index') : null],
                            ['key' => 'ticket', 'column' => 'ticket_enabled', 'label' => 'Ticket', 'icon' => 'support_agent', 'desc' => 'Halaman bantuan & tiket pengguna.', 'url' => auth()->user()->can('tickets.view') ? route('admin.tickets.index') : null],
                            ['key' => 'merchandise', 'column' => 'merchandise_enabled', 'label' => 'Merchandise', 'icon' => 'redeem', 'desc' => 'Katalog penukaran poin merchandise pengguna.', 'url' => auth()->user()->can('merchandise.view') ? route('admin.merchandise.index') : null],
                        ];
                    @endphp

                    @foreach ($features as $feature)
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 r-sm" style="border:1px solid #d3e3e1">
                            <div class="d-flex align-items-center gap-3">
                                <span class="ibox ibox-48 {{ $setting->{$feature['column']} ? 'bg-mint-l tx-mintt' : 'bg-errc-l tx-err' }}">
                                    <span class="ms">{{ $feature['icon'] }}</span>
                                </span>
                                <div>
                                    <div class="fw-semibold">{{ $feature['label'] }}</div>
                                    <div class="tx-mute fs-12">{{ $feature['desc'] }}</div>
                                    @if ($feature['url'])
                                        <a href="{{ $feature['url'] }}" class="fs-12">Kelola data {{ strtolower($feature['label']) }} →</a>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="pill {{ $setting->{$feature['column']} ? 'bg-mint-l tx-mintt' : 'bg-errc-l tx-err' }}">
                                    {{ $setting->{$feature['column']} ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                <x-ui.switch :name="$feature['column']" :checked="$setting->{$feature['column']}" />
                            </div>
                        </div>
                    @endforeach

                    @can('features.update')
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn-navy">
                                <span class="ms ms-sm align-middle me-1">save</span>Simpan
                            </button>
                        </div>
                    @endcan
                </form>
            </x-ui.card>
        </div>

        <div class="col-lg-4">
            <x-ui.card title="Keterangan">
                <ul class="list-unstyled m-0 d-flex flex-column gap-3 fs-12 tx-mute">
                    <li class="d-flex gap-2">
                        <span class="ms ms-sm tx-tealt">info</span>
                        <span>Switch <strong>aktif</strong> menampilkan halaman seperti biasa.</span>
                    </li>
                    <li class="d-flex gap-2">
                        <span class="ms ms-sm tx-tealt">info</span>
                        <span>Switch <strong>nonaktif</strong> menampilkan halaman "Coming Soon" kepada pengguna.</span>
                    </li>
                    <li class="d-flex gap-2">
                        <span class="ms ms-sm tx-tealt">info</span>
                        <span>Pengaturan ini hanya memengaruhi halaman pengguna, bukan menu admin.</span>
                    </li>
                </ul>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection
