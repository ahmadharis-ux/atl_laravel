@extends('layouts.app')

@section('title', 'Notifikasi - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('dashboard') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div class="flex-grow-1">
            <h1 class="fw-bold fs-4 m-0">Notifikasi</h1>
            <p class="tx-mute fs-12 m-0">Notifikasi yang belum dibaca. Setelah dibuka, otomatis hilang dari daftar.</p>
        </div>
        @if ($unread > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}" class="m-0">
                @csrf
                <button type="submit" class="btn-soft">
                    <span class="ms ms-md align-middle me-1">done_all</span>Tandai semua dibaca
                </button>
            </form>
        @endif
    </div>

    <x-ui.card>
        <div class="notif-page">
            @include('partials.notifications-list', ['notifications' => $notifications])
        </div>
    </x-ui.card>

    <div class="d-flex justify-content-center">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
