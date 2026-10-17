@extends('emails.layout', ['emailTitle' => 'Atur Ulang Kata Sandi - Atlantic Pedia'])

@section('content')
    <span style="display:inline-block;background:#fff3d6;color:#a76b00;font-size:11px;font-weight:700;padding:4px 12px;border-radius:999px;margin-bottom:14px;">Keamanan Akun</span>

    <h1 style="margin:0 0 12px;font-size:21px;line-height:1.3;font-weight:800;color:#06283a;">Atur ulang kata sandi</h1>

    <p style="margin:0 0 22px;font-size:14px;line-height:1.75;color:#42474c;">
        Halo <strong style="color:#151d1e;">{{ $name }}</strong>, kami menerima permintaan untuk mengatur ulang kata sandi akun
        Atlantic Pedia kamu. Tekan tombol di bawah untuk membuat kata sandi baru.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
            <td style="border-radius:10px;background:#00696f;">
                <a href="{{ $url }}" style="display:inline-block;padding:13px 30px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">Atur Ulang Kata Sandi</a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 6px;font-size:12px;line-height:1.7;color:#8b9296;">Tombol tidak berfungsi? Salin dan tempel tautan berikut ke browser:</p>
    <p style="margin:0 0 22px;font-size:12px;line-height:1.6;color:#0b6f76;word-break:break-all;">{{ $url }}</p>

    <div style="background:#f3fbfc;border:1px solid #e7eff0;border-radius:12px;padding:14px 16px;font-size:12px;line-height:1.7;color:#42474c;">
        Tautan ini berlaku selama {{ $count }} menit. Jika kamu tidak meminta pengaturan ulang kata sandi, abaikan saja email ini&mdash;kata sandi kamu tetap aman.
    </div>
@endsection
