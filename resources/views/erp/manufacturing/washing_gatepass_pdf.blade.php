<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Washing Gate Pass GP-{{ $transfer->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; margin: 0; padding: 0; }
        .header { border-bottom: 3px solid #0c4a6e; padding-bottom: 10px; margin-bottom: 14px; }
        .company { font-size: 18px; font-weight: bold; color: #0c4a6e; }
        .doc-title { font-size: 15px; font-weight: bold; margin-top: 4px; letter-spacing: 0.5px; }
        .meta { font-size: 10px; color: #555; margin-top: 4px; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.info td { padding: 6px 8px; border: 1px solid #cbd5e1; vertical-align: top; }
        table.info .lbl { width: 28%; background: #f1f5f9; font-weight: bold; color: #334155; font-size: 10px; text-transform: uppercase; }
        table.info .val { font-size: 12px; font-weight: bold; }
        .barcode-box { text-align: center; border: 2px dashed #94a3b8; padding: 12px; margin: 14px 0; }
        .barcode-box img { height: 55px; }
        .barcode-text { font-family: DejaVu Sans Mono, monospace; font-size: 11px; margin-top: 6px; letter-spacing: 1px; }
        .signatures { width: 100%; margin-top: 18px; border-collapse: collapse; }
        .signatures td { width: 33.33%; padding: 8px; vertical-align: bottom; text-align: center; }
        .sign-line { border-top: 1px solid #333; margin-top: 36px; padding-top: 4px; font-size: 10px; font-weight: bold; }
        .sign-role { font-size: 9px; color: #64748b; text-transform: uppercase; margin-top: 2px; }
        .sign-name { font-size: 11px; margin-top: 4px; }
        .footer { margin-top: 14px; font-size: 9px; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        .badge { display: inline-block; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">{{ config('app.name') }}</div>
        <div class="doc-title">WASHING GATE PASS</div>
        <div class="meta">
            Gate Pass No: <strong>GP-{{ $transfer->id }}</strong> &nbsp;|&nbsp;
            Issued: {{ $transfer->created_at->format('d M Y, H:i') }}
        </div>
    </div>

    <table class="info">
        <tr>
            <td class="lbl">Job / Order Number</td>
            <td class="val">{{ $jobNo }}</td>
            <td class="lbl">Queue Number</td>
            <td class="val">{{ $queueNo }}</td>
        </tr>
        <tr>
            <td class="lbl">Style Reference</td>
            <td class="val">{{ $transfer->job?->style_ref ?? '—' }}</td>
            <td class="lbl">Product</td>
            <td class="val">{{ $transfer->job?->product?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Quantity Sent</td>
            <td class="val">{{ number_format($transfer->quantity_sent) }} pieces</td>
            <td class="lbl">Sent From</td>
            <td class="val">{{ $transfer->sent_from }}</td>
        </tr>
        <tr>
            <td class="lbl">Washing Type</td>
            <td class="val"><span class="badge">{{ $transfer->washing_type ? $transfer->washing_type_label : '—' }}</span></td>
            <td class="lbl">Vehicle Number</td>
            <td class="val">{{ $transfer->vehicle_number ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Washing Factory</td>
            <td class="val" colspan="3">{{ $transfer->washing_factory_name ?? '—' }}</td>
        </tr>
        @if($transfer->remarks)
        <tr>
            <td class="lbl">Remarks</td>
            <td class="val" colspan="3">{{ $transfer->remarks }}</td>
        </tr>
        @endif
    </table>

    <div class="barcode-box">
        <img src="data:image/png;base64,{{ $barcodePng }}" alt="Barcode">
        <div class="barcode-text">{{ $barcodeValue }}</div>
        <div style="font-size:9px;color:#64748b;margin-top:4px;">Scan this barcode at the washing factory to receive goods</div>
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sign-line">Admin / Production Authority</div>
                <div class="sign-role">Authorized signature</div>
                <div class="sign-name">{{ $transfer->admin_signatory ?? '—' }}</div>
            </td>
            <td>
                <div class="sign-line">Driver</div>
                <div class="sign-role">Transport signature</div>
                <div class="sign-name">{{ $transfer->driver_signatory ?? '—' }}</div>
            </td>
            <td>
                <div class="sign-line">Washing Factory</div>
                <div class="sign-role">Factory representative</div>
                <div class="sign-name">{{ $transfer->factory_signatory ?? '—' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        This document is an official gate pass for transferring finished garments from production to an external washing factory.
        Please retain a printed copy for your records. &mdash; {{ config('app.name') }}
    </div>
</body>
</html>
