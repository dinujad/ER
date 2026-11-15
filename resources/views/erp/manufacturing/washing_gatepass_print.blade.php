<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Washing Gate Pass GP-{{ $transfer->id }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f1f5f9; margin: 0; padding: 16px; color: #0f172a; }
        .toolbar { margin-bottom: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
        .btn {
            display: inline-block; padding: 8px 14px; border-radius: 6px; background: #0c4a6e; color: #fff;
            text-decoration: none; border: 0; cursor: pointer; font-weight: 600; font-size: 14px;
        }
        .btn-outline { background: #fff; color: #0c4a6e; border: 1px solid #0c4a6e; }
        .paper {
            max-width: 800px; margin: 0 auto; background: #fff; border: 1px solid #cbd5e1;
            border-radius: 8px; padding: 24px 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        .header { border-bottom: 3px solid #0c4a6e; padding-bottom: 12px; margin-bottom: 18px; }
        .company { font-size: 22px; font-weight: 700; color: #0c4a6e; }
        .doc-title { font-size: 17px; font-weight: 700; margin-top: 4px; letter-spacing: 0.04em; }
        .meta { color: #64748b; font-size: 13px; margin-top: 6px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 20px; margin-bottom: 16px; }
        .field { border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; }
        .label { color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; }
        .value { font-size: 15px; font-weight: 700; margin-top: 4px; }
        .full { grid-column: 1 / -1; }
        .barcode-wrap {
            margin: 20px 0; text-align: center; border: 2px dashed #94a3b8;
            border-radius: 8px; padding: 16px; background: #f8fafc;
        }
        .barcode-wrap img { max-width: 100%; height: 70px; }
        .barcode-text { font-family: monospace; font-size: 13px; margin-top: 8px; letter-spacing: 1px; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 24px; }
        .sign-box { text-align: center; padding-top: 8px; }
        .sign-line { border-top: 1px solid #334155; margin-top: 48px; padding-top: 8px; font-weight: 700; font-size: 12px; }
        .sign-role { font-size: 11px; color: #64748b; text-transform: uppercase; margin-top: 4px; }
        .sign-name { font-size: 14px; margin-top: 6px; }
        .footer { margin-top: 20px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 12px; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .paper { border: 0; box-shadow: none; max-width: none; padding: 12px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn" type="button" onclick="window.print()">Print Gate Pass</button>
        <a class="btn btn-outline" href="{{ route('manufacturing.washing.print', ['id' => $transfer->id, 'format' => 'pdf']) }}">Download PDF</a>
    </div>

    <div class="paper">
        <div class="header">
            <div class="company">{{ config('app.name') }}</div>
            <div class="doc-title">WASHING GATE PASS</div>
            <div class="meta">
                Gate Pass No: <strong>GP-{{ $transfer->id }}</strong> &nbsp;|&nbsp;
                Issued: {{ $transfer->created_at->format('d M Y, H:i') }}
            </div>
        </div>

        <div class="grid">
            <div class="field">
                <div class="label">Job / Order Number</div>
                <div class="value">{{ $jobNo }}</div>
            </div>
            <div class="field">
                <div class="label">Queue Number</div>
                <div class="value">{{ $queueNo }}</div>
            </div>
            <div class="field">
                <div class="label">Style Reference</div>
                <div class="value">{{ $transfer->job?->style_ref ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="label">Product</div>
                <div class="value">{{ $transfer->job?->product?->name ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="label">Quantity Sent</div>
                <div class="value">{{ number_format($transfer->quantity_sent) }} pieces</div>
            </div>
            <div class="field">
                <div class="label">Sent From</div>
                <div class="value">{{ $transfer->sent_from }}</div>
            </div>
            <div class="field">
                <div class="label">Washing Type</div>
                <div class="value">{{ $transfer->washing_type_label }}</div>
            </div>
            <div class="field">
                <div class="label">Vehicle Number</div>
                <div class="value">{{ $transfer->vehicle_number }}</div>
            </div>
            <div class="field full">
                <div class="label">Washing Factory</div>
                <div class="value">{{ $transfer->washing_factory_name }}</div>
            </div>
            @if($transfer->remarks)
            <div class="field full">
                <div class="label">Remarks</div>
                <div class="value" style="font-weight:500;font-size:14px;">{{ $transfer->remarks }}</div>
            </div>
            @endif
        </div>

        <div class="barcode-wrap">
            <img src="data:image/png;base64,{{ $barcodePng }}" alt="Gate pass barcode">
            <div class="barcode-text">{{ $barcodeValue }}</div>
            <div style="font-size:12px;color:#64748b;margin-top:6px;">Scan at washing factory to receive goods</div>
        </div>

        <div class="signatures">
            <div class="sign-box">
                <div class="sign-line">Admin / Production Authority</div>
                <div class="sign-role">Authorized signature</div>
                <div class="sign-name">{{ $transfer->admin_signatory }}</div>
            </div>
            <div class="sign-box">
                <div class="sign-line">Driver</div>
                <div class="sign-role">Transport signature</div>
                <div class="sign-name">{{ $transfer->driver_signatory }}</div>
            </div>
            <div class="sign-box">
                <div class="sign-line">Washing Factory</div>
                <div class="sign-role">Factory representative</div>
                <div class="sign-name">{{ $transfer->factory_signatory }}</div>
            </div>
        </div>

        <div class="footer">
            Official gate pass — {{ config('app.name') }} — retain for records
        </div>
    </div>
</body>
</html>
