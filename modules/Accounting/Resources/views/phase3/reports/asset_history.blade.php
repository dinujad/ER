<h2>Asset history — {{ $asset->asset_code }}</h2>
<table border="1" cellpadding="4"><tr><th>Date</th><th>Type</th><th>Description</th></tr>
@foreach($events as $ev)<tr><td>{{ $ev['date'] }}</td><td>{{ $ev['type'] }}</td><td>{{ $ev['description'] }}</td></tr>@endforeach
</table>