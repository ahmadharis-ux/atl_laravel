@extends('layouts.app')

@section('title', $ticket->code.' - Bantuan Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('tickets.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div class="flex-fill">
            <h1 class="fw-bold fs-4 m-0">{{ $ticket->subject }}</h1>
            <p class="tx-mute fs-12 m-0">{{ $ticket->code }} &middot; {{ $ticket->category->label() }} &middot; {{ $ticket->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</p>
        </div>
        <span class="pill {{ $ticket->status->tone() }}">{{ $ticket->status->label() }}</span>
    </div>

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span>
            <span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-errc-l">
            <span class="ms tx-err">error</span>
            <span class="fw-semibold fs-12 tx-err">{{ session('error') }}</span>
        </div>
    @endif

    <div class="card-ap r-card p-3 d-flex flex-column gap-3">
        <div class="d-flex gap-2">
            <span class="ibox ibox-40 bg-tealc-m tx-tealt flex-shrink-0"><span class="ms" style="font-size:20px">{{ $ticket->category->icon() }}</span></span>
            <div>
                <div class="fw-bold fs-12">{{ auth()->user()->name }}</div>
                <p class="fs-12 m-0" style="white-space:pre-line">{{ $ticket->description }}</p>
                @if ($ticket->company_name)
                    <div class="tx-mute fs-11 mt-1">Perusahaan: {{ $ticket->company_name }} &middot; {{ $ticket->company_phone }}</div>
                @endif
            </div>
        </div>

        @foreach ($ticket->replies as $reply)
            <div class="d-flex gap-2 {{ $reply->is_staff ? 'flex-row-reverse text-end' : '' }}">
                <span class="ibox ibox-40 flex-shrink-0 {{ $reply->is_staff ? 'bg-mint-l tx-mintt' : 'bg-tealc-m tx-tealt' }}">
                    <span class="ms" style="font-size:20px">{{ $reply->is_staff ? 'support_agent' : 'person' }}</span>
                </span>
                <div class="flex-fill">
                    <div class="fw-bold fs-12">{{ $reply->is_staff ? 'Tim Atlantic Pedia' : ($reply->user?->name ?? 'Pengguna') }}</div>
                    <p class="fs-12 m-0" style="white-space:pre-line">{{ $reply->body }}</p>
                    <div class="tx-mute fs-11 mt-1">{{ $reply->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</div>
                </div>
            </div>
        @endforeach
    </div>

    @if (! $ticket->status->isFinal())
        <form action="{{ route('tickets.reply', $ticket) }}" method="POST" class="card-ap r-card p-3 d-flex flex-column gap-2">
            @csrf
            <x-ui.textarea name="body" label="Balas tiket" :rows="3" placeholder="Tulis balasan untuk tim kami..." />
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn-ap btn-mint">
                    <span class="ms ms-sm align-middle me-1">send</span>Kirim Balasan
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
