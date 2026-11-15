<h2>Asset disposals</h2>
<table border="1" cellpadding="4"><tr><th>Date</th><th>Asset</th><th>Proceeds</th></tr>
@foreach($rows as $row)<tr><td>{{ $row['date'] }}</td><td>{{ $row['asset'] }}</td><td>{{ $row['proceeds'] }}</td></tr>@endforeach
</table>