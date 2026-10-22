@extends('layouts.app')

@section('title', 'Bantuan & Tiket - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('dashboard') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
            <div>
                <h1 class="fw-bold fs-4 m-0">Bantuan</h1>
                <p class="tx-mute fs-12 m-0">Aduan, saran, dan pengajuan supplier.</p>
            </div>
        </div>
        <a href="{{ route('tickets.create') }}" class="btn-ap btn-mint">
            <span class="ms ms-sm align-middle me-1">add</span>Buat Tiket
        </a>
    </div>

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span>
            <span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif

    <div class="card-ap r-card p-3">
        <ul class="trx list-unstyled m-0">
            @forelse ($tickets as $ticket)
                <li data-row-href="{{ route('tickets.show', $ticket) }}">
                    <span class="ibox ibox-44 bg-tealc-m tx-tealt"><span class="ms" style="font-size:20px">{{ $ticket->category->icon() }}</span></span>
                    <div class="t">
                        <div class="fw-bold text-truncate">{{ $ticket->subject }}</div>
                        <div class="fs-12 tx-mute text-truncate">{{ $ticket->code }} &middot; {{ $ticket->category->label() }}</div>
                        <div class="fs-11 tx-skyd">{{ $ticket->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                        <span class="pill fs-11 {{ $ticket->status->tone() }}">{{ $ticket->status->label() }}</span>
                    </div>
                    <a href="{{ route('tickets.show', $ticket) }}" class="ap-icon-btn" data-tip="Detail"><span class="ms ms-md">chevron_right</span></a>
                </li>
            @empty
                <li class="d-block border-0 p-0">
                    <x-ui.empty-state icon="support_agent" title="Belum ada tiket" description="Ada kendala atau masukan? Buat tiket agar tim kami bisa membantu.">
                        <a href="{{ route('tickets.create') }}" class="btn-ap btn-mint justify-content-center">Buat Tiket</a>
                    </x-ui.empty-state>
                </li>
            @endforelse
        </ul>
    </div>

    @if ($tickets->hasPages())
        <div class="d-flex justify-content-center">{{ $tickets->links() }}</div>
    @endif
</div>
@endsection
