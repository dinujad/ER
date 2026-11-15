<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family: DejaVu Sans, sans-serif; font-size:12px} h1{font-size:18px}
table{width:100%;border-collapse:collapse;margin-top:12px} th,td{border:1px solid #ccc;padding:4px 6px}
th{background:#f3f3f3} .r{text-align:right} .muted{color:#666}
</style></head><body>
<h1>{{ $title }} ({{ $currency }})</h1>
<div class="muted">Generated {{ $generated_at }} · {{ $from }} to {{ $to }}</div>
<p><strong>{{ $party->name ?? ('Supplier #'.$party->id) }}</strong></p>
<p>Opening: {{ $opening }} · Bills: {{ $bill_total }} · Payments: {{ $paid_total }} · <strong>Closing: {{ $closing }}</strong></p>
<table><thead><tr><th>Bill</th><th>Date</th><th class="r">Amount</th><th>Status</th></tr></thead><tbody>
@foreach($bills as $b)
<tr><td>{{ $b->id }}</td><td>{{ $b->date ?? '' }}</td><td class="r">{{ number_format((float)($b->total ?? 0),2) }}</td><td>{{ $b->status ?? '' }}</td></tr>
@endforeach
</tbody></table>
<table><thead><tr><th>Payment</th><th>Date</th><th class="r">Amount</th></tr></thead><tbody>
@foreach($payments as $p)
<tr><td>{{ $p->id }}</td><td>{{ $p->payment_date ?? '' }}</td><td class="r">{{ number_format((float)($p->amount ?? 0),2) }}</td></tr>
@endforeach
</tbody></table>
</body></html>
