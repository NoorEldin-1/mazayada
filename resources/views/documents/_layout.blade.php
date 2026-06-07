<!DOCTYPE html>
<html lang="{{ locale_lang() }}" dir="{{ locale_dir() }}">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        @page { margin: 28px 32px; }
        body { color: #1f2937; font-size: 12px; line-height: 1.6; }
        .doc-header { width: 100%; border-bottom: 2px solid #15573f; padding-bottom: 10px; margin-bottom: 16px; }
        .doc-header td { vertical-align: top; }
        .brand { color: #15573f; font-size: 20px; font-weight: bold; }
        .doc-title { font-size: 15px; font-weight: bold; margin-top: 4px; }
        .doc-meta { font-size: 10px; color: #6b7280; }
        .qr-box { text-align: end; }
        .qr-box svg { width: 96px; height: 96px; }
        .section { margin: 14px 0; }
        .section h3 { font-size: 12px; color: #15573f; margin: 0 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
        table.kv { width: 100%; border-collapse: collapse; }
        table.kv td { padding: 3px 6px; font-size: 11px; }
        table.kv td.k { color: #6b7280; width: 38%; }
        table.fees { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.fees td, table.fees th { border: 1px solid #e5e7eb; padding: 5px 8px; font-size: 11px; text-align: start; }
        table.fees .total td { font-weight: bold; background: #f3f6f5; }
        .legal { font-size: 9px; color: #6b7280; margin-top: 14px; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .footer { font-size: 9px; color: #9ca3af; text-align: center; margin-top: 18px; }
        .stamp { display: inline-block; border: 2px solid #15573f; color: #15573f; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <table class="doc-header">
        <tr>
            <td style="width:70%">
                <div class="brand">{{ config('app.name', 'مزايدة') }}</div>
                <div class="doc-title">{{ $title }}</div>
                <div class="doc-meta">
                    {{ __('documents.common.doc_id') }}: {{ $docId }}<br>
                    {{ __('documents.common.issued_at') }}: {{ $issuedAt->format('Y-m-d H:i') }}
                </div>
            </td>
            <td class="qr-box" style="width:30%">
                @if(!empty($qrImage))<img src="{{ $qrImage }}" style="width:96px;height:96px" alt="QR">@endif
            </td>
        </tr>
    </table>

    @yield('doc-content')

    <div class="footer">
        {{ __('documents.common.verify_footer') }}<br>
        {{ $verifyUrl }}
    </div>
</body>
</html>
