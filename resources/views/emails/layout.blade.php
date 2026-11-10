<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $subject ?? 'BreIn' }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fa;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2d38;">
<span style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $preheader ?? '' }}</span>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fa;padding:32px 12px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">
            <tr><td style="padding:0 4px 18px;">
                <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                    <td style="background:#0f7b6c;border-radius:10px;width:36px;height:36px;text-align:center;color:#ffffff;font-weight:800;font-size:16px;">B</td>
                    <td style="padding-left:10px;font-size:22px;font-weight:800;color:#12232e;letter-spacing:-0.5px;">Bre<span style="color:#0f7b6c;">In</span></td>
                </tr></table>
            </td></tr>
            <tr><td style="background:#ffffff;border:1px solid #e3e8ee;border-radius:16px;padding:36px 32px;">
                @yield('content')
            </td></tr>
            <tr><td style="padding:20px 8px;text-align:center;font-size:12px;line-height:18px;color:#64727d;">
                BreIn · Home services marketplace · Casablanca, Morocco<br>
                You received this email because an action was requested on your BreIn account.
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
