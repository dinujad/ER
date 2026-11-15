<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .muted { color: #666; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f0f0f0; }
        .text-end { text-align: right; }
        .section { margin-top: 16px; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="muted">{{ $from }} to {{ $to }}</p>

    @if($report === 'business-summary')
        <div class="section">Sales</div>
        <table>
            <tr><td>Total Sales</td><td class="text-end">Rs {{ number_format($data['sales']['total'] ?? 0, 2) }}</td></tr>
            <tr><td>Collected</td><td class="text-end">Rs {{ number_format($data['sales']['collected'] ?? 0, 2) }}</td></tr>
            <tr><td>Outstanding</td><td class="text-end">Rs {{ number_format($data['sales']['outstanding'] ?? 0, 2) }}</td></tr>
        </table>
        <div class="section">Purchases &amp; Expenses</div>
        <table>
            <tr><td>Purchases</td><td class="text-end">Rs {{ number_format($data['purchases']['total'] ?? 0, 2) }}</td></tr>
            <tr><td>Expenses</td><td class="text-end">Rs {{ number_format($data['expenses']['total'] ?? 0, 2) }}</td></tr>
        </table>
        <div class="section">Production</div>
        <table>
            <tr><td>Total Production Cost</td><td class="text-end">Rs {{ number_format($data['production']['total_cost'] ?? 0, 2) }}</td></tr>
            <tr><td>Transport</td><td class="text-end">Rs {{ number_format($data['production']['transport'] ?? 0, 2) }}</td></tr>
            <tr><td>Sewing</td><td class="text-end">Rs {{ number_format($data['production']['sewing'] ?? 0, 2) }}</td></tr>
            <tr><td>Washing</td><td class="text-end">Rs {{ number_format($data['production']['washing'] ?? 0, 2) }}</td></tr>
            <tr><td><strong>Estimated Margin</strong></td><td class="text-end"><strong>Rs {{ number_format($data['summary']['estimated_margin'] ?? 0, 2) }}</strong></td></tr>
        </table>
    @elseif($report === 'cost-report')
        @php $avg = $data['simple_average'] ?? []; @endphp
        <div class="section">Simple Average (Expenses ÷ pieces)</div>
        <table>
            <tr><td>Total Expenses</td><td class="text-end">Rs {{ number_format($avg['total_expenses'] ?? 0, 2) }}</td></tr>
            <tr><td>Finished pieces</td><td class="text-end">{{ number_format($avg['finished_pieces'] ?? 0) }}</td></tr>
            <tr><td><strong>Overhead / piece</strong></td><td class="text-end"><strong>Rs {{ number_format($avg['overhead_per_piece'] ?? 0, 2) }}</strong></td></tr>
        </table>
        @if(!empty($avg['expense_lines']))
            <table>
                <thead><tr><th>Expense category</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    @foreach($avg['expense_lines'] as $line)
                        <tr>
                            <td>{{ $line['label'] }}</td>
                            <td class="text-end">{{ number_format($line['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <div class="section">Revenue (Collections)</div>
        <table>
            <tr><td>Collections</td><td class="text-end">Rs {{ number_format($data['revenue']['collections'] ?? 0, 2) }}</td></tr>
            <tr><td>Refunds</td><td class="text-end">Rs {{ number_format($data['revenue']['refunds'] ?? 0, 2) }}</td></tr>
            <tr><td><strong>Net Revenue</strong></td><td class="text-end"><strong>Rs {{ number_format($data['revenue']['net'] ?? 0, 2) }}</strong></td></tr>
        </table>
        @foreach($data['topics'] ?? [] as $topic)
            <div class="section">{{ $topic['label'] }} — Rs {{ number_format($topic['total'] ?? 0, 2) }}</div>
            <table>
                <thead><tr><th>Line</th><th>Source</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    @forelse($topic['lines'] ?? [] as $line)
                        <tr>
                            <td>{{ $line['label'] }}</td>
                            <td>{{ $line['source'] }}</td>
                            <td class="text-end">{{ number_format($line['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No costs</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endforeach
        <div class="section">Net Profit</div>
        <table>
            <tr><td>Total Costs</td><td class="text-end">Rs {{ number_format($data['summary']['total_costs'] ?? 0, 2) }}</td></tr>
            <tr><td><strong>Net Profit</strong></td><td class="text-end"><strong>Rs {{ number_format($data['summary']['net_profit'] ?? 0, 2) }}</strong></td></tr>
        </table>
    @elseif($report === 'cost-analyst')
        <div class="section">Summary</div>
        <table>
            <tr><td>Net Revenue</td><td class="text-end">Rs {{ number_format($data['revenue']['net'] ?? 0, 2) }}</td></tr>
            <tr><td>Ledger total (filtered)</td><td class="text-end">Rs {{ number_format($data['summary']['ledger_total'] ?? 0, 2) }}</td></tr>
            <tr><td>Cost sheet total</td><td class="text-end">Rs {{ number_format($data['summary']['total_costs'] ?? 0, 2) }}</td></tr>
            <tr><td><strong>Net Profit</strong></td><td class="text-end"><strong>Rs {{ number_format($data['summary']['net_profit'] ?? 0, 2) }}</strong></td></tr>
        </table>
        <div class="section">All expense &amp; cost lines</div>
        <table>
            <thead><tr><th>Date</th><th>Source</th><th>Category</th><th>Description</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                @foreach($data['ledger'] ?? [] as $line)
                    <tr>
                        <td>{{ $line['date'] }}</td>
                        <td>{{ $line['source'] }}</td>
                        <td>{{ $line['category'] }}</td>
                        <td>{{ Str::limit($line['description'] ?? '', 40) }}</td>
                        <td class="text-end">{{ number_format($line['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="section">Category Totals</div>
        <table>
            <thead><tr><th>Category</th><th class="text-end">Amount (Rs)</th></tr></thead>
            <tbody>
                @foreach($data['category_totals'] ?? [] as $cat)
                    @if(($cat['amount'] ?? 0) > 0)
                    <tr><td>{{ $cat['label'] }}</td><td class="text-end">{{ number_format($cat['amount'], 2) }}</td></tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        <div class="section">Job Summary</div>
        <table>
            <thead><tr><th>Job</th><th class="text-end">Total</th><th class="text-end">Transport</th></tr></thead>
            <tbody>
                @foreach($data['job_rows'] ?? [] as $row)
                    @php $cats = $row['additional_by_category'] ?? []; @endphp
                    <tr>
                        <td>{{ $row['job']->job_number }}</td>
                        <td class="text-end">{{ number_format($row['total'], 2) }}</td>
                        <td class="text-end">{{ number_format($cats['transport']['amount'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
