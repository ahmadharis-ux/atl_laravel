@extends('emails.layout', ['emailTitle' => 'Verifikasi Email - Atlantic Pedia'])

@section('content')
    <span style="display:inline-block;background:rgba(100,253,185,.25);color:#00a06c;font-size:11px;font-weight:700;padding:4px 12px;border-radius:999px;margin-bottom:14px;">Verifikasi Akun</span>

    <h1 style="margin:0 0 12px;font-size:21px;line-height:1.3;font-weight:800;color:#06283a;">Verifikasi email kamu</h1>

    <p style="margin:0 0 22px;font-size:14px;line-height:1.75;color:#42474c;">
        Halo <strong style="color:#151d1e;">{{ $name }}</strong>, terima kasih telah mendaftar di Atlantic Pedia.
        Satu langkah lagi&mdash;konfirmasi alamat email kamu dengan menekan tombol di bawah ini.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
            <td style="border-radius:10px;background:#00696f;">
                <a href="{{ $url }}" style="display:inline-block;padding:13px 30px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">Verifikasi Email</a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 6px;font-size:12px;line-height:1.7;color:#8b9296;">Tombol tidak berfungsi? Salin dan tempel tautan berikut ke browser:</p>
    <p style="margin:0 0 22px;font-size:12px;line-height:1.6;color:#0b6f76;word-break:break-all;">{{ $url }}</p>

    <div style="background:#f3fbfc;border:1px solid #e7eff0;border-radius:12px;padding:14px 16px;font-size:12px;line-height:1.7;color:#42474c;">
        Tautan ini bersifat rahasia. Jika kamu tidak merasa membuat akun di Atlantic Pedia, abaikan saja email ini.
    </div>
@endsection
