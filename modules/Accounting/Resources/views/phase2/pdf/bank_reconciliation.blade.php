<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family: DejaVu Sans, sans-serif; font-size:12px} h1{font-size:18px}
table{width:100%;border-collapse:collapse;margin-top:10px} th,td{border:1px solid #ccc;padding:4px 6px}
th{background:#f3f3f3} .r{text-align:right} .muted{color:#666}
</style></head><body>
<h1>{{ $title }} ({{ $currency }})</h1>
<div class="muted">Generated {{ $generated_at }} · {{ $from }} to {{ $to }} · Prepared by {{ $prepared_by }}</div>
<p><strong>{{ $account->gl_code ?? '' }} — {{ $account->name ?? '' }}</strong></p>
<p>Book balance: {{ $book_balance }}
@if($statement_ending) · Statement ending: {{ $statement_ending }} · Difference: {{ $difference }} @endif
</p>
<h2 style="font-size:14px">Cleared / Matched</h2>
<table><thead><tr><th>Date</th><th>Description</th><th class="r">Debit</th><th class="r">Credit</th><th>Match</th></tr></thead><tbody>
@foreach($cleared as $c)
<tr><td>{{ optional($c->txn_date)->format('Y-m-d') }}</td><td>{{ $c->description }}</td>
<td class="r">{{ number_format((float)$c->debit,2) }}</td><td class="r">{{ number_format((float)$c->credit,2) }}</td>
<td>{{ $c->matched_source_type }}#{{ $c->matched_source_id }}</td></tr>
@endforeach
</tbody></table>
<h2 style="font-size:14px">Outstanding (unmatched statement)</h2>
<table><thead><tr><th>Date</th><th>Description</th><th class="r">Debit</th><th class="r">Credit</th></tr></thead><tbody>
@foreach($outstanding as $o)
<tr><td>{{ optional($o->txn_date)->format('Y-m-d') }}</td><td>{{ $o->description }}</td>
<td class="r">{{ number_format((float)$o->debit,2) }}</td><td class="r">{{ number_format((float)$o->credit,2) }}</td></tr>
@endforeach
</tbody></table>
</body></html>
