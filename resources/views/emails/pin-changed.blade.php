@extends('emails.layout', ['emailTitle' => 'PIN Transaksi Diubah - Atlantic Pedia'])

@section('content')
    <span style="display:inline-block;background:#e4f2f0;color:#00696f;font-size:11px;font-weight:700;padding:4px 12px;border-radius:999px;margin-bottom:14px;">Keamanan Akun</span>

    <h1 style="margin:0 0 12px;font-size:21px;line-height:1.3;font-weight:800;color:#06283a;">PIN transaksi berhasil diubah</h1>

    <p style="margin:0 0 20px;font-size:14px;line-height:1.75;color:#42474c;">
        Halo <strong style="color:#151d1e;">{{ $name }}</strong>, PIN transaksi akun Atlantic Pedia kamu baru saja diubah
        pada {{ $changedAt }}. Semua perangkat lain yang masih login juga sudah dikeluarkan.
    </p>

    <div style="background:#f3fbfc;border:1px solid #e7eff0;border-radius:12px;padding:14px 16px;font-size:12px;line-height:1.7;color:#42474c;">
        Jika kamu tidak melakukan perubahan ini, segera ubah kata sandi akun dan hubungi tim Atlantic Pedia.
        Jangan bagikan PIN atau kode OTP kepada siapa pun.
    </div>
@endsection
