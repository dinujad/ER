<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Production Report — {{ $job->job_number }} — {{ $date }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; background: #fff; padding: 20px 24px; }
        .report-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1e3a5f; padding-bottom: 10px; margin-bottom: 14px; }
        .report-header h1 { font-size: 16px; color: #1e3a5f; margin-bottom: 4px; }
        .report-header .meta { color: #555; font-size: 11px; line-height: 1.7; }
        .print-btn { background: #1e3a5f; color: #fff; border: none; padding: 7px 18px; font-size: 12px; border-radius: 4px; cursor: pointer; }
        .summary-row { display: flex; gap: 12px; margin-bottom: 18px; }
        .summary-box { flex: 1; border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 14px; background: #f8fafc; text-align: center; }
        .summary-box .label { font-size: 10px; color: #6b7280; text-transform: uppercase; margin-bottom: 4px; }
        .summary-box .value { font-size: 22px; font-weight: bold; color: #1e3a5f; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        thead tr { background: #1e3a5f; color: #fff; }
        thead th { padding: 8px 10px; border: 1px solid #1e3a5f; text-align: center; }
        thead th.hour-col { text-align: left; }
        tbody td { padding: 6px 10px; border: 1px solid #d1d5db; text-align: center; }
        tbody td.hour-col { text-align: left; font-weight: 600; }
        tbody td.zero { color: #9ca3af; }
        tbody td.has-qty { color: #065f46; font-weight: bold; }
        tfoot tr { background: #1e3a5f; color: #fff; font-weight: bold; }
        tfoot td { padding: 8px 10px; border: 1px solid #1e3a5f; text-align: center; }
        tfoot td.hour-col { text-align: left; }
        @media print { .print-btn { display: none; } body { padding: 8px; } }
    </style>
</head>
<body>

<div class="report-header">
    <div>
        <h1>Hourly Production Report</h1>
        <div class="meta">
            <div><strong>Job:</strong> {{ $job->job_number }} | <strong>Style:</strong> {{ $job->style_ref ?? '—' }}</div>
            <div><strong>Lines:</strong> {{ $assignedLines->isNotEmpty() ? $assignedLines->implode(', ') : '—' }} | <strong>Date:</strong> {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</div>
        </div>
    </div>
    <div>
        <button class="print-btn" onclick="window.print()">Print</button>
    </div>
</div>

@php $hours = range($startHour, $endHour); @endphp

<div class="summary-row">
    <div class="summary-box">
        <div class="label">Total Production</div>
        <div class="value">{{ $grandTotal }} Pcs</div>
    </div>
    <div class="summary-box">
        <div class="label">Lines</div>
        <div class="value" style="font-size:16px;">{{ $assignedLines->count() }}</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th class="hour-col">Hour</th>
            @foreach($assignedLines as $line)
                <th>{{ $line }}</th>
            @endforeach
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($hours as $h)
            @php
                $rowTotal = 0;
                foreach ($assignedLines as $line) {
                    $rowTotal += (int) ($matrix[$h][$line] ?? 0);
                }
            @endphp
            <tr>
                <td class="hour-col">{{ sprintf('%02d:00', $h) }} – {{ sprintf('%02d:00', $h+1) }}</td>
                @foreach($assignedLines as $line)
                    @php $qty = (int) ($matrix[$h][$line] ?? 0); @endphp
                    <td class="{{ $qty > 0 ? 'has-qty' : 'zero' }}">{{ $qty > 0 ? $qty : '—' }}</td>
                @endforeach
                <td class="{{ $rowTotal > 0 ? 'has-qty' : 'zero' }}">{{ $rowTotal > 0 ? $rowTotal : '—' }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="hour-col">TOTAL</td>
            @foreach($assignedLines as $line)
                @php
                    $colTotal = 0;
                    foreach ($hours as $h) {
                        $colTotal += (int) ($matrix[$h][$line] ?? 0);
                    }
                @endphp
                <td>{{ $colTotal }}</td>
            @endforeach
            <td>{{ $grandTotal }}</td>
        </tr>
    </tfoot>
</table>

</body>
</html>
