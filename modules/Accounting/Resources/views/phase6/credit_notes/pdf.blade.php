<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CREDIT NOTE - {{ $cn->credit_note_number }}</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 20px; margin: 0; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; }
        .box td { border: 1px solid #333; padding: 5px 7px; vertical-align: top; }
        .items th, .items td { border: 1px solid #333; padding: 5px 7px; }
        .items th { background: #eee; text-align: left; }
        .r { text-align: right; }
        .muted { color: #555; }
        .mt { margin-top: 10px; }
    </style>
</head>
<body>
@php $order = $cn->order; $isTax = filled($order?->customer?->tin); @endphp
<table>
    <tr>
        <td>
            <div style="font-size:15px;font-weight:bold;">{{ $company['name'] ?? 'DERMAS APPAREL (PVT) LTD' }}</div>
            <div class="muted">{{ $company['address'] ?? '' }}<br>{{ $company['telephone'] ?? '' }}</div>
            <div>Supplier's TIN: {{ $company['tin'] ?? '—' }}</div>
        </td>
        <td class="r"><h1>{{ $isTax ? 'TAX CREDIT NOTE' : 'CREDIT NOTE' }}</h1>
            <div class="mt">No. <strong>{{ $cn->credit_note_number }}</strong></div>
            <div>Date: {{ $cn->credit_note_date?->format('d/m/Y') }}</div>
            @if($cn->isVoid())<div style="color:#c00;font-weight:bold;">VOID</div>@endif
        </td>
    </tr>
</table>

<table class="box mt">
    <tr>
        <td style="width:50%">
            <strong>Customer</strong><br>
            {{ $order?->customer?->business_name ?: $order?->customer?->name }}<br>
            {{ $order?->customer?->display_address ?? '' }}<br>
            Purchaser's TIN: {{ $order?->customer?->tin ?: '—' }}
        </td>
        <td>
            <strong>Original invoice</strong><br>
            {{ $order?->invoice_number }} dated {{ $order?->invoiceDate()?->format('d/m/Y') }}<br>
            Original invoice total: Rs {{ number_format((float) $order?->invoice_total, 2) }}<br>
            Reason: {{ $cn->reason }}
        </td>
    </tr>
</table>

<table class="items mt">
    <thead><tr><th>Description</th><th class="r" style="width:12%">Qty</th><th class="r" style="width:18%">Unit price</th><th class="r" style="width:20%">Amount (Rs.)</th></tr></thead>
    <tbody>
    @if($cn->type === 'return' && $cn->saleReturn)
        @foreach($cn->saleReturn->items as $item)
            <tr>
                <td>{{ $item->product?->name ?? 'Returned item' }}</td>
                <td class="r">{{ number_format((float) $item->quantity, 2) }}</td>
                <td class="r">{{ number_format((float) ($item->unit_price ?? 0), 2) }}</td>
                <td class="r">{{ number_format((float) ($item->line_total ?? ((float) $item->quantity * (float) ($item->unit_price ?? 0))), 2) }}</td>
            </tr>
        @endforeach
    @else
        <tr><td>{{ $cn->reason }}</td><td class="r"></td><td class="r"></td><td class="r">{{ number_format($cn->goods_amount, 2) }}</td></tr>
    @endif
        <tr><td colspan="3" class="r">Value of goods credited (excl. tax)</td><td class="r">{{ number_format($cn->goods_amount, 2) }}</td></tr>
        @if($cn->sscl_amount > 0)<tr><td colspan="3" class="r">SSCL credited</td><td class="r">{{ number_format($cn->sscl_amount, 2) }}</td></tr>@endif
        @if($cn->vat_amount > 0)<tr><td colspan="3" class="r">VAT credited</td><td class="r">{{ number_format($cn->vat_amount, 2) }}</td></tr>@endif
        <tr><td colspan="3" class="r"><strong>Total credit</strong></td><td class="r"><strong>{{ number_format($cn->total, 2) }}</strong></td></tr>
    </tbody>
</table>

<p class="mt muted">This credit note reduces the amount payable on invoice {{ $order?->invoice_number }}.@if($isTax) Issued under Inland Revenue VAT credit note requirements.@endif</p>
<table class="mt" style="margin-top:40px;">
    <tr>
        <td style="width:33%;border-top:1px solid #333;text-align:center;">Prepared by</td>
        <td style="width:34%"></td>
        <td style="width:33%;border-top:1px solid #333;text-align:center;">Customer acknowledgement</td>
    </tr>
</table>
</body>
</html>
