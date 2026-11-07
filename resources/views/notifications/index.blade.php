@extends('layouts.app')

@section('title', 'Notifikasi - Atlantic Pedia Tab')

@php
    $rp = auth()->user()?->is_admin ? 'admin.' : '';
@endphp

@section('content')
<div class="d-flex flex-column gap-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route($rp . 'dashboard') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div class="flex-grow-1">
            <h1 class="fw-bold fs-4 m-0">Notifikasi</h1>
            <p class="tx-mute fs-12 m-0">Semua notifikasi tersimpan di sini. Filter atau cari kapan saja.</p>
        </div>
        @if ($unread > 0)
            <form method="POST" action="{{ route($rp . 'notifications.read-all') }}" class="m-0">
                @csrf
                <button type="submit" class="btn-soft">
                    <span class="ms ms-md align-middle me-1">done_all</span>Tandai semua dibaca
                </button>
            </form>
        @endif
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="seg-tabs" role="tablist">
            <a href="{{ route($rp . 'notifications.index', array_filter(['filter' => 'all', 'q' => $search])) }}"
               class="seg-tab {{ $filter === 'all' ? 'is-active' : '' }}" role="tab">
                Semua <span class="seg-count">{{ $total }}</span>
            </a>
            <a href="{{ route($rp . 'notifications.index', array_filter(['filter' => 'unread', 'q' => $search])) }}"
               class="seg-tab {{ $filter === 'unread' ? 'is-active' : '' }}" role="tab">
                Belum dibaca <span class="seg-count">{{ $unread }}</span>
            </a>
            <a href="{{ route($rp . 'notifications.index', array_filter(['filter' => 'read', 'q' => $search])) }}"
               class="seg-tab {{ $filter === 'read' ? 'is-active' : '' }}" role="tab">
                Sudah dibaca <span class="seg-count">{{ $readCount }}</span>
            </a>
        </div>

        <form method="GET" action="{{ route($rp . 'notifications.index') }}" class="d-flex align-items-center gap-2">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <div class="d-flex align-items-center bg-white r-sm px-3" style="border:1px solid #d3e3e1;height:2.5rem">
                <span class="ms ms-md tx-mute me-2">search</span>
                <input name="q" value="{{ $search }}" class="border-0 bg-transparent fs-12" style="outline:0;width:12rem" placeholder="Cari notifikasi...">
            </div>
            @if ($search !== '')
                <a href="{{ route($rp . 'notifications.index', ['filter' => $filter]) }}" class="btn-soft" data-tip="Bersihkan"><span class="ms ms-sm align-middle">close</span></a>
            @endif
        </form>
    </div>

    <div data-ajax
         data-ajax-url="{{ route($rp . 'notifications.list', array_filter(['filter' => $filter !== 'all' ? $filter : null, 'q' => $search !== '' ? $search : null])) }}">
        <x-ui.card>
            <div class="notif-page" data-ajax-rows>
                @include('partials.notifications-list', [
                    'notifications' => $notifications,
                    'emptyMessage' => $emptyMessage,
                ])
            </div>
        </x-ui.card>

        <div class="d-flex justify-content-center mt-3" data-ajax-footer>
            @include('partials.notifications-footer', ['notifications' => $notifications])
        </div>
    </div>
</div>
@endsection