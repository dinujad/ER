<h2>Cost center P&amp;L</h2>
<p>{{ $from }} – {{ $to }}</p>
<p>Income: {{ $report['income'] }} · Expense: {{ $report['expense'] }} · Net: {{ $report['net'] }}</p>
<table border="1" cellpadding="4"><tr><th>GL</th><th>Name</th><th>Amount</th></tr>
@foreach($report['lines'] as $line)
<tr><td>{{ $line['gl_code'] }}</td><td>{{ $line['name'] }}</td><td>{{ $line['amount'] }}</td></tr>
@endforeach
</table>