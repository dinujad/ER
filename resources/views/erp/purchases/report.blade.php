<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Report — {{ $purchase->purchase_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #111;
            background: #fff;
            padding: 20px 24px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            gap: 16px;
        }
        .company-block {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            flex: 1;
        }
        .company-logo { max-height: 56px; max-width: 140px; object-fit: contain; }
        .company-name { font-size: 18px; font-weight: 700; color: #1e3a5f; margin-bottom: 2px; }
        .company-meta { font-size: 11px; color: #555; line-height: 1.5; }
        .doc-title {
            text-align: right;
            border: 2px solid #1e3a5f;
            border-radius: 6px;
            padding: 10px 16px;
            min-width: 200px;
        }
        .doc-title h1 { font-size: 16px; color: #1e3a5f; margin-bottom: 4px; letter-spacing: .5px; }
        .doc-title .po-no { font-size: 14px; font-weight: 700; }
        .doc-title .printed { font-size: 10px; color: #666; margin-top: 6px; }
        .print-actions { display: flex; flex-direction: column; align-items: flex-end; }
        .print-btn {
            background: #1e3a5f; color: #fff; border: none;
            padding: 7px 18px; font-size: 12px; border-radius: 4px; cursor: pointer;
            margin-bottom: 6px;
        }
        .print-btn:hover { background: #2c5282; }
        .back-link { font-size: 11px; color: #1e3a5f; text-decoration: none; }
        .section {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            margin-bottom: 14px;
            overflow: hidden;
        }
        .section-title {
            background: #f1f5f9;
            padding: 7px 12px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #1e3a5f;
            border-bottom: 1px solid #d1d5db;
        }
        .section-body { padding: 12px; }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 24px;
        }
        .info-grid .label { color: #6b7280; font-size: 10px; text-transform: uppercase; }
        .info-grid .value { font-weight: 600; margin-top: 2px; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-draft { background: #e5e7eb; color: #374151; }
        .badge-confirmed { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead tr { background: #1e3a5f; color: #fff; }
        thead th { padding: 8px 10px; text-align: left; border: 1px solid #1e3a5f; }
        thead th.num, tbody td.num, tfoot td.num { text-align: right; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 7px 10px; border: 1px solid #d1d5db; vertical-align: top; }
        tfoot tr { background: #f1f5f9; font-weight: 700; }
        tfoot td { padding: 8px 10px; border: 1px solid #d1d5db; }
        .totals-wrap { display: flex; justify-content: flex-end; margin-top: 4px; padding: 0 12px 12px; }
        .totals-table { width: 280px; border-collapse: collapse; }
        .totals-table td { padding: 5px 10px; border-bottom: 1px solid #e5e7eb; }
        .totals-table td:last-child { text-align: right; font-weight: 600; }
        .totals-table tr.grand td { border-top: 2px solid #1e3a5f; font-size: 14px; font-weight: 700; color: #1e3a5f; }
        .totals-table tr.balance td { color: #b91c1c; }
        .totals-table tr.paid td { color: #065f46; }
        .notes { font-size: 11px; color: #444; line-height: 1.5; white-space: pre-wrap; }
        .sig-area { display: flex; gap: 60px; margin-top: 32px; }
        .sig-line { border-top: 1px solid #111; padding-top: 4px; min-width: 150px; text-align: center; font-size: 11px; flex: 1; }
        .report-footer {
            margin-top: 20px; border-top: 1px solid #d1d5db; padding-top: 8px;
            display: flex; justify-content: space-between; font-size: 10px; color: #6b7280;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 8px; }
            @page { size: A4 portrait; margin: 12mm; }
        }
    </style>
</head>
<body>
@php
    $statusClass = match($purchase->status) {
        'confirmed' => 'badge-confirmed',
        'cancelled' => 'badge-cancelled',
        default => 'badge-draft',
    };
    $statusLabel = match($purchase->status) {
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
        default => 'Draft',
    };
    $supplier = $purchase->supplier;
@endphp

<div class="top-bar">
    <div class="company-block">
        @if(file_exists(public_path('images/logo.png')))
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="company-logo">
        @endif
        <div>
            <div class="company-name">{{ config('app.name') }}</div>
            @if(config('app.business_address'))
                <div class="company-meta">{{ config('app.business_address') }}</div>
            @endif
        </div>
    </div>
    <div class="print-actions no-print">
        <button type="button" class="print-btn" onclick="window.print()">Print Report</button>
        <a href="{{ route('purchases.show', $purchase) }}" class="back-link">← Back to Purchase</a>
    </div>
    <div class="doc-title">
        <h1>Purchase Report</h1>
        <div class="po-no">{{ $purchase->purchase_no }}</div>
        <div class="printed">Printed: {{ now()->format('d M Y, h:i A') }}</div>
    </div>
</div>

<div class="section">
    <div class="section-title">Purchase &amp; Supplier</div>
    <div class="section-body">
        <div class="info-grid">
            <div>
                <div class="label">Purchase Date</div>
                <div class="value">{{ $purchase->date->format('d M Y') }}</div>
            </div>
            <div>
                <div class="label">Status</div>
                <div class="value"><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></div>
            </div>
            <div>
                <div class="label">Purchase Type</div>
                <div class="value">{{ $purchase->purchase_type_label }}</div>
            </div>
            <div>
                <div class="label">Reference No.</div>
                <div class="value">{{ $purchase->reference_no ?: '—' }}</div>
            </div>
            <div>
                <div class="label">Supplier</div>
                <div class="value">{{ $supplier->name ?? '—' }}</div>
                @if($supplier && $supplier->company_name)
                    <div style="font-size:11px;color:#555;margin-top:2px;">{{ $supplier->company_name }}</div>
                @endif
            </div>
            <div>
                <div class="label">Supplier Contact</div>
                <div class="value" style="font-weight:400;line-height:1.5;">
                    @if($supplier?->phone)Tel: {{ $supplier->phone }}<br>@endif
                    @if($supplier?->email){{ $supplier->email }}<br>@endif
                    @if($supplier?->tax_vat_no)VAT: {{ $supplier->tax_vat_no }}@endif
                    @if(!$supplier?->phone && !$supplier?->email && !$supplier?->tax_vat_no)—@endif
                </div>
            </div>
            @if($supplier?->address)
            <div style="grid-column: 1 / -1;">
                <div class="label">Supplier Address</div>
                <div class="value" style="font-weight:400;">{{ $supplier->address }}</div>
            </div>
            @endif
            @if($purchase->confirmed_at)
            <div>
                <div class="label">Confirmed At</div>
                <div class="value" style="font-weight:400;">{{ $purchase->confirmed_at->format('d M Y H:i') }}</div>
            </div>
            @endif
            @if($purchase->creator)
            <div>
                <div class="label">Created By</div>
                <div class="value" style="font-weight:400;">{{ $purchase->creator->name ?? '—' }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="section">
    <div class="section-title">Items</div>
    <table>
        <thead>
            <tr>
                <th style="width:36px;">#</th>
                <th>Item</th>
                <th>Unit</th>
                <th class="num">Qty</th>
                <th class="num">Buy Price</th>
                <th class="num">Discount</th>
                <th class="num">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->material->name ?? '—' }}</td>
                <td>{{ $item->unit ?? '—' }}</td>
                <td class="num">{{ number_format($item->quantity, 2) }}</td>
                <td class="num">{{ number_format($item->buy_price, 2) }}</td>
                <td class="num">{{ number_format($item->discount, 2) }}</td>
                <td class="num">{{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="num">Subtotal</td>
                <td class="num">{{ number_format($purchase->subtotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>
    <div class="totals-wrap">
        <table class="totals-table">
            <tr><td>Discount</td><td>{{ number_format($purchase->discount_amount, 2) }}</td></tr>
            <tr><td>Tax</td><td>{{ number_format($purchase->tax_amount, 2) }}</td></tr>
            <tr><td>Shipping</td><td>{{ number_format($purchase->shipping_cost, 2) }}</td></tr>
            <tr class="grand"><td>Grand Total</td><td>Rs {{ number_format($purchase->total, 2) }}</td></tr>
            <tr class="paid"><td>Total Paid</td><td>Rs {{ number_format($purchase->total_paid, 2) }}</td></tr>
            <tr class="balance"><td>Balance Due</td><td>Rs {{ number_format($purchase->balance, 2) }}</td></tr>
        </table>
    </div>
</div>

@if(count($paymentLines) > 0)
<div class="section">
    <div class="section-title">Payment History</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Method / Details</th>
                <th class="num">Amount (Rs)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paymentLines as $line)
            <tr>
                <td>{{ $line['date'] instanceof \DateTimeInterface ? $line['date']->format('d M Y') : \Illuminate\Support\Carbon::parse($line['date'])->format('d M Y') }}</td>
                <td>
                    @include('erp.purchases._payment-detail', [
                        'method' => $line['method'],
                        'reference' => $line['reference'],
                        'chequeNumber' => $line['cheque_number'],
                        'chequeDate' => $line['cheque_date'],
                        'chequeBank' => $line['cheque_bank'],
                    ])
                </td>
                <td class="num">{{ number_format($line['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="num">Total Paid</td>
                <td class="num">{{ number_format($purchase->total_paid, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

@if($purchase->notes || $purchase->cancel_reason)
<div class="section">
    <div class="section-title">Notes</div>
    <div class="section-body">
        @if($purchase->notes)
            <p class="notes"><strong>Notes:</strong> {{ $purchase->notes }}</p>
        @endif
        @if($purchase->cancel_reason)
            <p class="notes" style="margin-top:8px;color:#991b1b;"><strong>Cancellation reason:</strong> {{ $purchase->cancel_reason }}</p>
        @endif
    </div>
</div>
@endif

<div class="sig-area">
    <div class="sig-line">Prepared By</div>
    <div class="sig-line">Checked By</div>
    <div class="sig-line">Authorized Signature</div>
</div>

<div class="report-footer">
    <span>{{ config('app.name') }} — Purchase Report</span>
    <span>{{ $purchase->purchase_no }} | {{ $purchase->date->format('d M Y') }} | Generated {{ now()->format('Y-m-d H:i') }}</span>
</div>
</body>
</html>
