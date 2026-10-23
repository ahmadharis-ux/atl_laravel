@extends('layouts.app')

@section('title', 'Merchandise - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('dashboard') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-5 m-0">Merchandise</h1>
            <p class="tx-mute fs-12 m-0">Tukar poin reward kamu dengan merchandise menarik.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert-ap r-sm bg-mint-l tx-mintt d-flex align-items-center gap-2">
            <span class="ms">check_circle</span>
            <span class="fs-13 fw-semibold">{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-ap r-sm bg-errc-l tx-err d-flex align-items-center gap-2">
            <span class="ms">error</span>
            <span class="fs-13 fw-semibold">{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="card-ap r-card p-3 d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="ibox ibox-44 bg-gold tx-gold"><span class="ms">star</span></span>
            <div>
                <div class="fs-12 tx-mute">Poin kamu</div>
                <div class="num fw-bold fs-4">{{ number_format($points, 0, ',', '.') }} <span class="fs-6 tx-gold">Poin</span></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @forelse ($items as $item)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card-ap r-card p-3 h-100 d-flex flex-column gap-2">
                    @if ($url = $item->imageUrl())
                        <img src="{{ $url }}" alt="{{ $item->name }}" class="r-sm bg-white" style="width:100%;height:9rem;object-fit:contain">
                    @else
                        <div class="r-sm bg-glass d-flex align-items-center justify-content-center" style="height:9rem">
                            <span class="ms tx-mute" style="font-size:40px">redeem</span>
                        </div>
                    @endif
                    <div class="fw-bold fs-13">{{ $item->name }}</div>
                    @if ($item->description)
                        <div class="tx-mute fs-12 text-truncate">{{ $item->description }}</div>
                    @endif
                    <div class="d-flex align-items-center justify-content-between mt-auto pt-2">
                        <span class="num fw-bold tx-gold fs-13">{{ number_format($item->points_cost, 0, ',', '.') }} poin</span>
                        @if ($item->stock < 1)
                            <span class="pill bg-errc-l tx-err fs-11">Habis</span>
                        @else
                            <span class="pill bg-mint-l tx-mintt fs-11">Stok {{ $item->stock }}</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('merchandise.redeem', $item) }}">
                        @csrf
                        <button type="submit"
                            class="btn-ap {{ $item->stock > 0 && $points >= $item->points_cost ? 'btn-mint' : 'btn-ap-outline' }} w-100 justify-content-center"
                            @if ($item->stock < 1 || $points < $item->points_cost) disabled @endif
                            data-confirm="Tukar {{ number_format($item->points_cost, 0, ',', '.') }} poin untuk {{ $item->name }}?">
                            <span class="ms ms-sm align-middle me-1">redeem</span>Tukar
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-12">
                <x-ui.empty-state icon="redeem" title="Belum ada merchandise"
                    description="Katalog merchandise akan segera hadir. Terus kumpulkan poinmu!" />
            </div>
        @endforelse
    </div>

    <x-ui.card title="Riwayat Poin">
        <ul class="trx list-unstyled m-0">
            @forelse ($pointHistory as $entry)
                <li>
                    <span class="ibox ibox-44 {{ $entry->points_change >= 0 ? 'bg-mint-l tx-mintt' : 'bg-gold tx-gold' }}"><span class="ms" style="font-size:20px">{{ $entry->type->icon() }}</span></span>
                    <div class="t">
                        <div class="fw-bold text-truncate">{{ $entry->type->label() }}</div>
                        <div class="fs-12 tx-mute text-truncate">{{ $entry->description }} • {{ $entry->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                        <span class="num fw-bold {{ $entry->points_change >= 0 ? 'tx-mintt' : 'tx-gold' }}">{{ $entry->points_change >= 0 ? '+' : '' }}{{ number_format($entry->points_change, 0, ',', '.') }} poin</span>
                        <span class="fs-11 tx-mute num">Saldo {{ number_format($entry->points_after, 0, ',', '.') }}</span>
                    </div>
                </li>
            @empty
                <li class="d-block border-0 p-0">
                    <x-ui.empty-state icon="history" title="Belum ada aktivitas poin"
                        description="Perolehan dan penukaran poin kamu akan tampil di sini." />
                </li>
            @endforelse
        </ul>
    </x-ui.card>

    <x-ui.card title="Riwayat Penukaran">
        <ul class="trx list-unstyled m-0">
            @forelse ($redemptions as $redemption)
                <li>
                    <span class="ibox ibox-44 bg-gold tx-gold"><span class="ms" style="font-size:20px">redeem</span></span>
                    <div class="t">
                        <div class="fw-bold text-truncate">{{ $redemption->merchandise?->name ?? 'Merchandise dihapus' }}</div>
                        <div class="fs-12 tx-mute">{{ $redemption->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                        <span class="num fw-bold">{{ number_format($redemption->points_cost, 0, ',', '.') }} poin</span>
                        <span class="pill fs-11 {{ $redemption->status->tone() }}">{{ $redemption->status->label() }}</span>
                    </div>
                </li>
            @empty
                <li class="d-block border-0 p-0">
                    <x-ui.empty-state icon="history" title="Belum ada penukaran"
                        description="Riwayat penukaran merchandise kamu akan tampil di sini." />
                </li>
            @endforelse
        </ul>
    </x-ui.card>
</div>
@endsection
