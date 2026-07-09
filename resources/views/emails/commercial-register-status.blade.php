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

                            @if(($reason ?? null) && $group === 'cr_rejected')
                            <div style="background:#FBE2E0;border-radius:12px;padding:14px 18px;margin:0 0 24px;">
                                <div style="font-size:12px;color:#8E2F2A;margin-bottom:4px;">{{ __('mail.cr_rejected.reason_label') }}</div>
                                <div style="font-size:14px;color:#8E2F2A;font-weight:600;">{{ $reason }}</div>
                            </div>
                            @endif

                            <div style="text-align:center;margin:8px 0 0;">
                                <a href="{{ route('citizen.commercial-register') }}" style="display:inline-block;background:#15573f;color:#ffffff;text-decoration:none;border-radius:10px;padding:12px 28px;font-size:14px;font-weight:600;">{{ __("mail.{$group}.cta") }}</a>
                            </div>
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
