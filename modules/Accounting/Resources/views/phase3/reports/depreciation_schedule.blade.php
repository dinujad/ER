<h2>Depreciation — {{ $asset->asset_code }}</h2>
<table border="1" cellpadding="4"><tr><th>Period</th><th>Amount</th><th>Status</th></tr>
@foreach($rows as $row)<tr><td>{{ $row['period'] }}</td><td>{{ $row['scheduled'] }}</td><td>{{ $row['status'] }}</td></tr>@endforeach
</table>