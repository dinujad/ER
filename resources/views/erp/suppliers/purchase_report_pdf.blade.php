<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Supplier Purchases — {{ $supplier->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h2 { font-size: 14px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 12px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        .text-right { text-align: right; }
        .summary { margin-top: 12px; width: 50%; }
        .summary td { font-weight: bold; }
    </style>
</head>
<body>
    <h2>{{ config('app.name') }} — Supplier Purchase Report</h2>
    <div class="meta">
        <strong>{{ $supplier->name }}</strong> ({{ $supplier->code }})<br>
        @if($supplier->company_name) Company: {{ $supplier->company_name }}<br>@endif
        Phone: {{ $supplier->phone }}<br>
        Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}<br>
        Generated: {{ now()->format('d M Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Purchase No</th>
                <th>Date</th>
                <th>Reference</th>
                <th>Type</th>
                <th class="text-right">Total (Rs)</th>
                <th class="text-right">Paid (Rs)</th>
                <th class="text-right">Balance (Rs)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $p)
            <tr>
                <td>{{ $p->purchase_no }}</td>
                <td>{{ $p->date?->format('d M Y') }}</td>
                <td>{{ $p->reference_no ?? '—' }}</td>
                <td>{{ $p->purchase_type_label }}</td>
                <td class="text-right">{{ number_format($p->total, 2) }}</td>
                <td class="text-right">{{ number_format($p->total_paid, 2) }}</td>
                <td class="text-right">{{ number_format($p->balance, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="7">No confirmed purchases in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr><td>Purchases in period</td><td class="text-right">{{ $periodSummary['count'] }}</td></tr>
        <tr><td>Total (Rs)</td><td class="text-right">{{ number_format($periodSummary['total'], 2) }}</td></tr>
        <tr><td>Paid (Rs)</td><td class="text-right">{{ number_format($periodSummary['paid'], 2) }}</td></tr>
        <tr><td>Balance (Rs)</td><td class="text-right">{{ number_format($periodSummary['balance'], 2) }}</td></tr>
    </table>
</body>
</html>
