@extends('layouts.app')

@section('title', 'Buat Tiket - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('tickets.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Buat Tiket</h1>
            <p class="tx-mute fs-12 m-0">Tim kami akan menindaklanjuti secepatnya.</p>
        </div>
    </div>

    <form action="{{ route('tickets.store') }}" method="POST" class="card-ap r-card p-3 d-flex flex-column gap-3">
        @csrf

        <x-ui.select
            name="category"
            label="Jenis tiket"
            :options="\App\Enums\TicketCategory::options()"
            :value="$category->value"
        />

        <x-ui.input name="subject" label="Subjek" placeholder="Ringkasan singkat kendala/masukan" required />
        <x-ui.textarea name="description" label="Detail" :rows="5" hint="Jelaskan kronologi, nomor transaksi, atau detail lain yang membantu." />

        <div class="row g-3">
            <div class="col-md-6">
                <x-ui.input name="company_name" label="Nama perusahaan" hint="Khusus pengajuan supplier." />
            </div>
            <div class="col-md-6">
                <x-ui.input name="company_phone" label="Telepon perusahaan" hint="Khusus pengajuan supplier." />
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('tickets.index') }}" class="btn-ap btn-soft">Batal</a>
            <button type="submit" class="btn-ap btn-mint">
                <span class="ms ms-sm align-middle me-1">send</span>Kirim Tiket
            </button>
        </div>
    </form>
</div>
@endsection
