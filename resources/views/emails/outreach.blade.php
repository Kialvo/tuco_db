<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $subject ?? '' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* Basic email-safe resets */
        body,table,td,a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table,td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; }
        body { margin:0; padding:0; width:100% !important; height:100% !important; }
        a { color:#0ea5e9; text-decoration: underline; }

        /* Container width */
        .wrapper { width:100%; background:#f8fafc; padding:24px 0; }
        .container { width:100%; max-width:600px; margin:0 auto; background:#ffffff; }
        .content { padding:24px; font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; color:#111827; font-size:15px; line-height:1.6; }
        .footer { color:#6b7280; font-size:12px; text-align:center; padding:16px 24px 24px; }

        .h1 { margin:0 0 12px; font-weight:600; font-size:18px; color:#111827; }
        .muted { color:#6b7280; }

        /* Paragraph spacing */
        .content p { margin:0 0 14px; }
        .content p:last-child { margin-bottom:0; }

        /* “preheader” text (hidden preview in inboxes) */
        .preheader { display:none; max-height:0; overflow:hidden; mso-hide:all; opacity:0; color:transparent; height:0; width:0; }
    </style>
</head>
<body>
<!-- Preheader (what Gmail shows in preview line) -->
<div class="preheader">
    {{ ($subject ?? 'Quick confirmation') }} — reply with your current guest post rates.
</div>

<div class="wrapper">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="container">
                    <tr>
                        <td class="content">
                            {{-- Optional heading; comment out if you don’t want it --}}
                            {{-- <div class="h1">{{ $subject ?? '' }}</div> --}}

                            @php
                                // Split into paragraphs on blank lines and keep inline line breaks within paragraphs
                                $paragraphs = preg_split("/\r?\n\r?\n/", trim($bodyText ?? ''));
                            @endphp

                            @foreach($paragraphs as $p)
                                <p>{!! nl2br(e($p)) !!}</p>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <td class="footer">
                            <div class="muted">
                                Sent by Menford • Please reply directly to this email with your rates.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
