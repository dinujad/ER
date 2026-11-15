<h2>Fixed Asset Register</h2>
<table border="1" cellpadding="4" cellspacing="0" width="100%">
    <tr><th>Code</th><th>Name</th><th>Cost</th><th>NBV</th></tr>
    @foreach($rows as $row)
        <tr>
            <td>{{ \App\Services\Accounting\FixedAssetReportService::sanitizeSpreadsheetCell($row['asset_code']) }}</td>
            <td>{{ \App\Services\Accounting\FixedAssetReportService::sanitizeSpreadsheetCell($row['name']) }}</td>
            <td>{{ $row['cost'] }}</td>
            <td>{{ $row['nbv'] }}</td>
        </tr>
    @endforeach
    <tr><td colspan="2"><strong>Totals</strong></td><td>{{ $totals['cost'] }}</td><td>{{ $totals['nbv'] }}</td></tr>
</table>