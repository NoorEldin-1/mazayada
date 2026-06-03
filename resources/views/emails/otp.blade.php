@php($group = $group ?? 'otp')
<!DOCTYPE html>
<html lang="{{ locale_lang() }}" dir="{{ locale_dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ __("mail.{$group}.subject") }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f5;font-family:Arial,'Segoe UI',Tahoma,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e6eae8;">
                    {{-- Header --}}
                    <tr>
                        <td style="background:#15573f;padding:28px 32px;text-align:center;">
                            <span style="color:#ffffff;font-size:22px;font-weight:700;letter-spacing:0.5px;">{{ config('app.name', 'Mazayada') }}</span>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px;text-align:{{ locale_dir() === 'rtl' ? 'right' : 'left' }};">
                            <p style="margin:0 0 10px;font-size:16px;color:#111827;font-weight:600;">{{ __("mail.{$group}.greeting", ['name' => $user->name]) }}</p>
                            <p style="margin:0 0 24px;font-size:14px;line-height:1.7;color:#4b5563;">{{ __("mail.{$group}.intro") }}</p>

                            <div style="text-align:center;margin:0 0 24px;">
                                <div style="font-size:12px;color:#6b7280;margin-bottom:10px;">{{ __("mail.{$group}.code_label") }}</div>
                                <div style="display:inline-block;background:#f0f7f3;border:1px dashed #15573f;border-radius:12px;padding:16px 28px;font-size:34px;font-weight:700;letter-spacing:10px;color:#15573f;font-family:'Courier New',monospace;direction:ltr;">{{ $otp }}</div>
                            </div>

                            <p style="margin:0 0 8px;font-size:13px;color:#b45309;">{{ __("mail.{$group}.expiry", ['minutes' => $expiresInMinutes]) }}</p>
                            <p style="margin:0;font-size:13px;line-height:1.7;color:#6b7280;">{{ __("mail.{$group}.ignore") }}</p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:18px 32px;background:#f9fafb;border-top:1px solid #eef1f0;text-align:center;">
                            <p style="margin:0;font-size:12px;color:#9ca3af;">{{ __("mail.{$group}.footer") }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
