@extends('layouts.app')

@section('title', 'Pengaturan - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">

    <div class="d-flex align-items-center gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Pengaturan</h1>
            <p class="tx-mute fs-12 m-0">Kelola profil, keamanan, dan perangkat akun Anda.</p>
        </div>
    </div>

    @php
        $items = [
            ['label' => 'Profil', 'desc' => 'Nama, email & nomor HP', 'icon' => 'person', 'tone' => 'bg-sky-l tx-navy', 'url' => route('settings.profile')],
            ['label' => 'Rekening Bank', 'desc' => 'Tujuan pencairan saldo', 'icon' => 'account_balance', 'tone' => 'bg-mint-l tx-mintt', 'url' => route('settings.banks.index')],
            ['label' => 'Ubah Kata Sandi', 'desc' => 'Perbarui kata sandi akun', 'icon' => 'lock', 'tone' => 'bg-tealc-m tx-tealt', 'url' => route('settings.password')],
            ['label' => 'Autentikasi Dua Faktor', 'desc' => 'Keamanan tambahan akun', 'icon' => 'verified_user', 'tone' => 'bg-gold tx-gold', 'url' => route('settings.two-factor')],
            ['label' => 'Perangkat Aktif', 'desc' => 'Kelola sesi login', 'icon' => 'devices', 'tone' => 'bg-errc-l tx-err', 'url' => route('settings.sessions')],
            ['label' => 'Notifikasi', 'desc' => 'Push notifikasi perangkat', 'icon' => 'notifications', 'tone' => 'bg-sky-l tx-navy', 'url' => route('settings.notifications')],
            ['label' => 'Merchandise', 'desc' => 'Tukar poin reward', 'icon' => 'redeem', 'tone' => 'bg-gold tx-gold', 'url' => route('merchandise.index')],
        ];
    @endphp

    <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-xl-3 m-0">
        @foreach ($items as $item)
            <div class="col">
                <a href="{{ $item['url'] }}" class="settings-card card-ap r-card h-100">
                    <span class="ibox ibox-48 {{ $item['tone'] }}"><span class="ms">{{ $item['icon'] }}</span></span>
                    <span class="t">
                        <span class="lbl">{{ $item['label'] }}</span>
                        <span class="desc">{{ $item['desc'] }}</span>
                    </span>
                    <span class="ms go align-self-center">chevron_right</span>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection
