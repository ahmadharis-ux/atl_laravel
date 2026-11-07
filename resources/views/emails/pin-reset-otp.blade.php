@extends('emails.layout', ['emailTitle' => 'Kode OTP Reset PIN - Atlantic Pedia'])

@section('content')
    <span style="display:inline-block;background:#fff3d6;color:#a76b00;font-size:11px;font-weight:700;padding:4px 12px;border-radius:999px;margin-bottom:14px;">Keamanan Akun</span>

    <h1 style="margin:0 0 12px;font-size:21px;line-height:1.3;font-weight:800;color:#06283a;">Kode OTP reset PIN</h1>

    <p style="margin:0 0 20px;font-size:14px;line-height:1.75;color:#42474c;">
        Halo <strong style="color:#151d1e;">{{ $name }}</strong>, kami menerima permintaan untuk mengubah PIN transaksi
        akun Atlantic Pedia kamu. Masukkan kode berikut pada halaman verifikasi.
    </p>

    <div style="background:#06283a;border-radius:12px;padding:20px;text-align:center;margin:0 0 22px;">
        <div style="color:#9ff0f7;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;margin-bottom:8px;">Kode OTP</div>
        <div style="color:#ffffff;font-size:32px;line-height:1.2;font-weight:800;letter-spacing:.32em;">{{ $code }}</div>
    </div>

    <div style="background:#f3fbfc;border:1px solid #e7eff0;border-radius:12px;padding:14px 16px;font-size:12px;line-height:1.7;color:#42474c;">
        Kode ini berlaku selama {{ $minutes }} menit dan hanya bisa dipakai sekali. Jika kamu tidak meminta perubahan PIN,
        abaikan email ini dan segera hubungi tim Atlantic Pedia.
    </div>
@endsection
