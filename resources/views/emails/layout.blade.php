<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $emailTitle ?? 'Atlantic Pedia' }}</title>
</head>
<body style="margin:0;padding:0;background:#e4f2f0;font-family:'Plus Jakarta Sans',Arial,Helvetica,sans-serif;color:#151d1e;-webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#e4f2f0;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 12px 30px rgba(6,40,58,.10);">
                    <tr>
                        <td style="background:#06283a;padding:26px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="left" valign="middle">
                                        <img src="{{ asset('assets/images/9.png') }}" alt="Atlantic Pedia" height="30" style="height:30px;display:block;border:0;">
                                        <div style="color:#9ff0f7;font-size:11px;font-weight:600;letter-spacing:.02em;margin-top:8px;">Atlantic Pedia Tab</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:34px 32px 30px;">
                            @yield('content')
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f3fbfc;border-top:1px solid #e7eff0;padding:20px 32px;">
                            <div style="color:#42474c;font-size:12px;line-height:1.7;">
                                Email ini dikirim otomatis oleh sistem Atlantic Pedia. Mohon jangan membalas email ini.
                            </div>
                            <div style="color:#8b9296;font-size:11px;margin-top:8px;">&copy; {{ date('Y') }} Atlantic Pedia. Seluruh hak dilindungi.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
