@extends('layouts.app')

@php
    $labels = [
        'reseller' => 'Reseller',
        'ticket' => 'Bantuan & Tiket',
        'merchandise' => 'Merchandise',
    ];
    $label = $labels[$feature] ?? 'Fitur';
@endphp

@section('title', $label.' - Segera Hadir - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('dashboard') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">{{ $label }}</h1>
            <p class="tx-mute fs-12 m-0">Halaman ini sedang kami siapkan.</p>
        </div>
    </div>

    <x-ui.empty-state
        icon="rocket_launch"
        :title="$label.' segera hadir'"
        description="Kami sedang menyiapkan halaman ini. Silakan kembali lagi nanti.">
        <a href="{{ route('dashboard') }}" class="btn-ap btn-navy">Kembali ke Beranda</a>
    </x-ui.empty-state>
</div>
@endsection
