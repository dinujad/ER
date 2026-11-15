<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family: DejaVu Sans, sans-serif; font-size:12px; color:#111}
h1{font-size:18px;margin:0 0 8px} table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{border:1px solid #ccc;padding:4px 6px} th{background:#f3f3f3} .r{text-align:right} .muted{color:#666}
</style></head><body>
<h1>{{ $title }} ({{ $currency }})</h1>
<div class="muted">Generated {{ $generated_at }} · Period {{ $from }} to {{ $to }}</div>
<p><strong>{{ $party->name ?? ('Customer #'.$party->id) }}</strong></p>
<p>Opening: {{ $opening }} · Invoices: {{ $invoice_total }} · Payments: {{ $paid_total }} · <strong>Closing: {{ $closing }}</strong></p>
<h2 style="font-size:14px">Invoices</h2>
<table><thead><tr><th>ID</th><th>Date</th><th class="r">Amount</th><th>Status</th></tr></thead><tbody>
@foreach($invoices as $o)
<tr><td>{{ $o->id }}</td><td>{{ $o->order_date ?? $o->created_at ?? '' }}</td><td class="r">{{ number_format((float)($o->total_amount ?? 0),2) }}</td><td>{{ $o->status ?? '' }}</td></tr>
@endforeach
</tbody></table>
<h2 style="font-size:14px">Payments</h2>
<table><thead><tr><th>ID</th><th>Date</th><th class="r">Amount</th><th>Method</th></tr></thead><tbody>
@foreach($payments as $p)
<tr><td>{{ $p->id }}</td><td>{{ $p->payment_date ?? '' }}</td><td class="r">{{ number_format((float)($p->amount ?? 0),2) }}</td><td>{{ $p->payment_method ?? '' }}</td></tr>
@endforeach
</tbody></table>
</body></html>
