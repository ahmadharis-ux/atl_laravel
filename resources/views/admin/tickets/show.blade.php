@extends('layouts.admin')

@section('title', 'Tiket '.$ticket->code.' - Admin Atlantic Pedia')
@section('page-title', 'Tiket & Aduan')

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

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.tickets.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
            <div>
                <h1 class="fw-bold fs-4 m-0">{{ $ticket->subject }}</h1>
                <p class="tx-mute fs-12 m-0">{{ $ticket->code }} &middot; {{ $ticket->category->label() }} &middot; {{ $ticket->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</p>
            </div>
        </div>
        <span class="pill {{ $ticket->status->tone() }}">{{ $ticket->status->label() }}</span>
    </div>

    <div class="row g-3">
        <div class="col-lg-8 d-flex flex-column gap-3">
            <x-ui.card title="Percakapan">
                <div class="d-flex flex-column gap-3">

                    <div class="d-flex gap-2">
                        <span class="ibox ibox-40 bg-tealc-m tx-tealt flex-shrink-0"><span class="ms" style="font-size:20px">{{ $ticket->category->icon() }}</span></span>
                        <div>
                            <div class="fw-bold fs-12">{{ $ticket->user?->name ?? 'Pengguna dihapus' }}</div>
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
                                <div class="fw-bold fs-12">{{ $reply->is_staff ? ($reply->user?->name ?? 'Staf').' (Staf)' : ($reply->user?->name ?? 'Pengguna') }}</div>
                                <p class="fs-12 m-0" style="white-space:pre-line">{{ $reply->body }}</p>
                                <div class="tx-mute fs-11 mt-1">{{ $reply->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card title="Balas Tiket">
                @can('tickets.update')
                    <form action="{{ route('admin.tickets.reply', $ticket) }}" method="POST" class="d-flex flex-column gap-2">
                        @csrf
                        <x-ui.textarea name="body" label="Balasan" :rows="3" placeholder="Tulis balasan untuk pengguna..." />
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn-navy">
                                <span class="ms ms-sm align-middle me-1">send</span>Kirim Balasan
                            </button>
                        </div>
                    </form>
                @else
                    <p class="tx-mute fs-12 m-0">Anda tidak memiliki izin untuk membalas tiket.</p>
                @endcan
            </x-ui.card>
        </div>

        <div class="col-lg-4 d-flex flex-column gap-3">
            <x-ui.card title="Detail Tiket">
                <dl class="row m-0 fs-12">
                    <dt class="col-5 tx-mute fw-normal">Kode</dt>
                    <dd class="col-7 fw-semibold mb-2">{{ $ticket->code }}</dd>
                    <dt class="col-5 tx-mute fw-normal">Jenis</dt>
                    <dd class="col-7 mb-2">{{ $ticket->category->label() }}</dd>
                    <dt class="col-5 tx-mute fw-normal">Dibuat</dt>
                    <dd class="col-7 mb-2">{{ $ticket->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</dd>
                    <dt class="col-5 tx-mute fw-normal">Ditutup</dt>
                    <dd class="col-7 mb-2">{{ $ticket->closed_at?->locale('id')->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                </dl>
            </x-ui.card>

            <x-ui.card title="Pengguna">
                <div class="d-flex align-items-center gap-2">
                    <div class="ibox bg-mint tx-mintd fw-bold rounded-circle" style="width:2.25rem;height:2.25rem">{{ strtoupper(substr($ticket->user?->name ?? 'U', 0, 1)) }}</div>
                    <div class="d-flex flex-column">
                        <span class="fw-semibold fs-12">{{ $ticket->user?->name ?? 'Pengguna dihapus' }}</span>
                        <span class="tx-mute fs-11">{{ $ticket->user?->email ?? '-' }}</span>
                        <span class="tx-mute fs-11">{{ $ticket->user?->phone ?? '-' }}</span>
                    </div>
                </div>
            </x-ui.card>

            @can('tickets.update')
                <x-ui.card title="Ubah Status">
                    <form action="{{ route('admin.tickets.status', $ticket) }}" method="POST" class="d-flex flex-column gap-2">
                        @csrf
                        @method('PUT')
                        <x-ui.select name="status" label="Status" :options="$statuses" :value="$ticket->status->value" />
                        <button type="submit" class="btn-navy">Simpan Status</button>
                    </form>
                </x-ui.card>
            @endcan
        </div>
    </div>
</div>
@endsection
