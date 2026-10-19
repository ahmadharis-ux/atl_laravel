@extends('layouts.admin')

@section('title', 'Pengaturan Loyalitas - Admin Atlantic Pedia')
@section('page-title', 'Pengaturan Loyalitas')

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
        <h1 class="fw-bold fs-4 m-0">Pengaturan Loyalitas</h1>
        <p class="tx-mute fs-12 m-0">Threshold poin untuk tier Silver, Gold, dan Platinum.</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card-ap r-card p-4">
                <h2 class="fw-bold fs-5 m-0 mb-3">Threshold Tier</h2>
                @can('loyalty.update')
                    <form action="{{ route('admin.loyalty-settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <x-ui.input type="number" name="gold_points" label="Gold (poin)" :value="$setting->gold_points" min="1" hint="Poin minimal untuk mencapai tier Gold." />
                            </div>
                            <div class="col-sm-6">
                                <x-ui.input type="number" name="platinum_points" label="Platinum (poin)" :value="$setting->platinum_points" min="1" hint="Poin minimal untuk mencapai tier Platinum." />
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <button type="submit" class="btn-navy">
                                <span class="ms ms-sm align-middle me-1">save</span>Simpan
                            </button>
                        </div>
                    </form>
                @else
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="fs-12 tx-mute">Gold</div>
                            <div class="fw-bold num fs-5">{{ number_format($setting->gold_points, 0, ',', '.') }} poin</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="fs-12 tx-mute">Platinum</div>
                            <div class="fw-bold num fs-5">{{ number_format($setting->platinum_points, 0, ',', '.') }} poin</div>
                        </div>
                    </div>
                @endcan
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-ap r-card p-4 h-100">
                <h2 class="fw-bold fs-5 m-0 mb-3">Tingkatan</h2>
                <ul class="list-unstyled m-0 d-flex flex-column gap-3">
                    @foreach ($tiers as $tier)
                        <li class="d-flex align-items-center gap-3">
                            <span class="pill tier-{{ $tier->value }} flex-shrink-0">
                                <span class="ms ms-sm me-1" style="font-size:14px">{{ $tier->icon() }}</span>{{ $tier->label() }}
                            </span>
                            <span class="tx-mute fs-12">
                                @if ($tier === \App\Enums\UserTier::Silver)
                                    0 – {{ number_format(max($setting->gold_points - 1, 0), 0, ',', '.') }} poin
                                @elseif ($tier === \App\Enums\UserTier::Gold)
                                    {{ number_format($setting->gold_points, 0, ',', '.') }} – {{ number_format(max($setting->platinum_points - 1, 0), 0, ',', '.') }} poin
                                @else
                                    ≥ {{ number_format($setting->platinum_points, 0, ',', '.') }} poin
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
