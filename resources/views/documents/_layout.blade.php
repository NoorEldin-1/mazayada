<!DOCTYPE html>
<html lang="{{ locale_lang() }}" dir="{{ locale_dir() }}">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        @page { margin: 28px 32px; }
        body { color: #1f2937; font-size: 12px; line-height: 1.6; }

        /* Header — platform brand + organizing entity, ruled off with a brand band. */
        .doc-header { width: 100%; border-bottom: 2.5px solid #15573f; padding-bottom: 12px; margin-bottom: 4px; }
        .doc-header td { vertical-align: top; }
        .brand-row td { vertical-align: middle; padding: 0; }
        .logo { width: 38px; height: 38px; }
        .logo-cell { width: 50px; }
        .brand { color: #15573f; font-size: 20px; font-weight: bold; line-height: 1.1; }
        .brand-sub { color: #6b7280; font-size: 8.5px; letter-spacing: .2px; }
        .issuer { margin-top: 9px; padding: 6px 10px; background: #f3f6f5; border-inline-start: 3px solid #15573f; border-radius: 4px; }
        .issuer-label { display: block; font-size: 8.5px; color: #6b7280; }
        .issuer-name { font-size: 13.5px; font-weight: bold; color: #15573f; }
        .doc-title { font-size: 15px; font-weight: bold; margin-top: 9px; color: #111827; }
        .doc-meta { font-size: 10px; color: #6b7280; margin-top: 3px; }
        .qr-box { text-align: end; }
        .qr-box svg, .qr-box img { width: 92px; height: 92px; }
        .qr-cap { font-size: 7.5px; color: #9ca3af; text-align: end; margin-top: 2px; }
        /* Thin gold accent line just under the header rule. */
        .accent { height: 2px; background: #D4A843; margin-bottom: 16px; }

        .section { margin: 14px 0; }
        .section h3 { font-size: 12px; color: #15573f; margin: 0 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
        table.kv { width: 100%; border-collapse: collapse; }
        table.kv td { padding: 3px 6px; font-size: 11px; }
        table.kv td.k { color: #6b7280; width: 38%; }
        table.fees { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.fees td, table.fees th { border: 1px solid #e5e7eb; padding: 5px 8px; font-size: 11px; text-align: start; }
        table.fees .total td { font-weight: bold; background: #f3f6f5; }
        .legal { font-size: 9px; color: #6b7280; margin-top: 14px; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .footer { font-size: 9px; color: #9ca3af; text-align: center; margin-top: 18px; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .stamp { display: inline-block; border: 2px solid #15573f; color: #15573f; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: bold; }
        .sig-fp { display: inline-block; margin-inline-start: 10px; font-size: 9px; color: #6b7280; letter-spacing: .5px; direction: ltr; }
    </style>
</head>
<body>
    <table class="doc-header">
        <tr>
            <td style="width:62%">
                <table class="brand-row">
                    <tr>
                        @if(!empty($logo))
                        <td class="logo-cell"><img src="{{ $logo }}" class="logo" alt=""></td>
                        @endif
                        <td>
                            <div class="brand">{{ config('app.name', 'مزايدة') }}</div>
                            <div class="brand-sub">{{ __('documents.common.platform_tagline') }}</div>
                        </td>
                    </tr>
                </table>

                @if(!empty($entityName))
                <div class="issuer">
                    <span class="issuer-label">{{ __('documents.common.organizing_entity') }}</span>
                    <span class="issuer-name">{{ $entityName }}</span>
                </div>
                @endif

                <div class="doc-title">{{ $title }}</div>
                <div class="doc-meta">
                    {{ __('documents.common.doc_id') }}: {{ $docId }}<br>
                    {{ __('documents.common.issued_at') }}: {{ $issuedAt->format('Y-m-d H:i') }}
                </div>
            </td>
            <td class="qr-box" style="width:38%">
                @if(!empty($qrImage))
                <img src="{{ $qrImage }}" alt="QR">
                <div class="qr-cap">{{ __('documents.common.qr_caption') }}</div>
                @else
                {{-- QR failed to generate: keep the document verifiable by printing
                     the verify link as text so it can be typed / clicked. --}}
                <div class="qr-cap" style="direction:ltr;text-align:end;word-break:break-all"><a href="{{ $verifyUrl }}" style="color:#15573f">{{ $verifyUrl }}</a></div>
                @endif
            </td>
        </tr>
    </table>
    <div class="accent"></div>

    @yield('doc-content')

    @isset($signatureFingerprint)
    <div class="section" style="margin-top:18px">
        <span class="stamp">{{ __('documents.common.electronic_signature') }}</span>
        <span class="sig-fp">{{ __('documents.common.signature_ref') }}: {{ $signatureFingerprint }}</span>
    </div>
    @endisset

    <div class="footer">
        {{ __('documents.common.verify_footer') }}<br>
        {{-- Wrap in an <a> so the URL is a real clickable link in the PDF, not
             plain text (mpdf only makes anchored URLs clickable). --}}
        <a href="{{ $verifyUrl }}" style="color:#15573f;text-decoration:underline;direction:ltr;word-break:break-all">{{ $verifyUrl }}</a>
    </div>
</body>
</html>
