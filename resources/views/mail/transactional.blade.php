<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $subject }}</title>
    <style>
        @media only screen and (max-width: 640px) {
            .email-shell { width: 100% !important; }
            .email-padding { padding-left: 20px !important; padding-right: 20px !important; }
        }
        @media (prefers-color-scheme: dark) {
            .email-page { background-color: #e8eef7 !important; }
            .email-surface { background-color: #ffffff !important; }
            .email-title, .email-body, .email-context-value { color: #102a43 !important; }
            .email-muted, .email-footer { color: #52677d !important; }
        }
    </style>
</head>
<body class="email-page" style="margin:0;padding:0;background-color:#e8eef7;color:#102a43;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;mso-hide:all;">
        {{ $preheader }}
    </div>
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;mso-hide:all;">&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background-color:#e8eef7;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" class="email-shell email-surface" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;max-width:600px;background-color:#ffffff;border:1px solid #d7e2ee;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td class="email-padding" style="padding:22px 36px;border-bottom:4px solid #1769aa;background-color:#ffffff;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding:0;">
                                        <img src="{{ asset('images/figma/home/logo-color.png') }}" alt="BantuDaftarin" width="160" style="display:block;width:160px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-padding" style="padding:34px 36px 30px;">
                            <p class="email-body" style="margin:0 0 18px;color:#102a43;font-size:16px;line-height:24px;">{{ $greeting }}</p>
                            <h1 class="email-title" style="margin:0 0 18px;color:#102a43;font-size:24px;line-height:32px;font-weight:700;letter-spacing:-0.3px;">{{ $title }}</h1>

                            @foreach ($paragraphs as $paragraph)
                                <p class="email-body" style="margin:0 0 14px;color:#253b53;font-size:16px;line-height:24px;">{{ $paragraph }}</p>
                            @endforeach

                            @if ($otpCode !== null)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;margin:22px 0;background-color:#f1f6fb;border:1px solid #cbdbea;border-radius:10px;">
                                    <tr>
                                        <td align="center" style="padding:20px 16px;">
                                            <span style="color:#102a43;font-size:30px;line-height:36px;font-weight:700;letter-spacing:5px;white-space:nowrap;">{{ $otpCode }}</span>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if ($context !== [])
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;margin:20px 0;background-color:#f7f9fc;border:1px solid #d7e2ee;border-radius:8px;">
                                    @foreach ($context as $label => $value)
                                        <tr>
                                            <td style="padding:{{ $loop->first ? '14px' : '0 14px 14px' }} 8px 0 14px;color:#52677d;font-size:13px;line-height:18px;vertical-align:top;width:38%;">{{ $label }}</td>
                                            <td class="email-context-value" style="padding:{{ $loop->first ? '14px' : '0 14px 14px' }} 14px 0 8px;color:#102a43;font-size:14px;line-height:20px;font-weight:600;vertical-align:top;">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @if (! empty($actionText) && ! empty($actionUrl))
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0 18px;">
                                    <tr>
                                        <td align="center" bgcolor="#1769aa" style="border-radius:7px;background-color:#1769aa;">
                                            <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 18px;color:#ffffff;font-size:15px;line-height:20px;font-weight:700;text-decoration:none;border:1px solid #1769aa;border-radius:7px;">{{ $actionText }}</a>
                                        </td>
                                    </tr>
                                </table>
                                <p class="email-muted" style="margin:0 0 16px;color:#52677d;font-size:13px;line-height:19px;">Jika tombol tidak berfungsi, gunakan tautan ini:<br><a href="{{ $actionUrl }}" style="color:#1769aa;text-decoration:underline;word-break:break-all;">{{ $actionUrl }}</a></p>
                            @endif

                            @foreach ($secondaryLines as $line)
                                <p class="email-muted" style="margin:0 0 12px;color:#52677d;font-size:14px;line-height:21px;">{{ $line }}</p>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <td class="email-padding email-footer" style="padding:18px 36px 22px;border-top:1px solid #d7e2ee;color:#52677d;font-size:12px;line-height:18px;">
                            Email transaksi otomatis dari BantuDaftarin.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
