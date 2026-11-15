<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 7px; color: #111; }
        h2 { font-size: 13px; color: #0c4a6e; margin: 0 0 4px; }
        .meta { font-size: 8px; color: #555; margin-bottom: 8px; }
        h3 { font-size: 9px; margin: 10px 0 4px; color: #334155; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 3px 4px; text-align: left; }
        th { background: #f1f5f9; font-size: 6px; text-transform: uppercase; }
        .text-right { text-align: right; }
        .summary td { font-weight: bold; }
    </style>
</head>
<body>
@php $pt = $data['production']['totals'] ?? []; $ws = $data['washing_summary'] ?? []; @endphp
    <h2>{{ config('app.name') }} — {{ $title }}</h2>
    <div class="meta">Period: {{ $from }} to {{ $to }} | Generated: {{ now()->format('d M Y H:i') }}</div>

    <table class="summary">
        <tr>
            <td>Jobs: {{ $data['job_count'] ?? 0 }}</td>
            <td>Line output: {{ number_format($pt['period_produced'] ?? 0) }}</td>
            <td>Line target: {{ number_format($pt['period_target'] ?? 0) }}</td>
            <td>Wash sent: {{ number_format($ws['total_sent'] ?? 0) }}</td>
            <td>Wash received: {{ number_format($ws['total_returned'] ?? 0) }}</td>
            <td>QC finished: {{ number_format($data['qc_finished'] ?? 0) }}</td>
        </tr>
    </table>

    <h3>Jobs</h3>
    @php $canSeeCosts = auth()->user()?->can_access('manufacturing.costs'); @endphp
    <table>
        <tr>
            <th>Job</th><th>Style</th><th>Status</th><th>Issue</th>
            <th class="text-right">Cut</th><th class="text-right">Target</th><th class="text-right">Produced</th><th class="text-right">Wash sent</th>
            @if($canSeeCosts)
            <th class="text-right">Fabric</th><th class="text-right">Access.</th><th class="text-right">Added</th><th class="text-right">Total</th><th class="text-right">Cost/pc</th>
            @endif
        </tr>
        @foreach($data['job_details'] ?? [] as $row)
            @php $j = $row['job']; @endphp
            <tr>
                <td>{{ $j->job_number }}</td>
                <td>{{ $j->style_ref ?? '' }}</td>
                <td>{{ $j->status }}</td>
                <td>{{ $j->issue_date?->format('Y-m-d') }}</td>
                <td class="text-right">{{ $row['cut_pieces'] }}</td>
                <td class="text-right">{{ $row['line_target'] }}</td>
                <td class="text-right">{{ $row['line_produced'] }}</td>
                <td class="text-right">{{ $row['washing_sent'] }}</td>
                @if($canSeeCosts)
                <td class="text-right">{{ number_format($row['fabric_cost'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($row['accessory_cost'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($row['additional_cost'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($row['total_cost'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($row['cost_per_unit'] ?? 0, 2) }}</td>
                @endif
            </tr>
        @endforeach
    </table>

    <h3>Line production</h3>
    <table>
        <tr><th>Job</th><th>Line</th><th class="text-right">Target</th><th class="text-right">Total</th><th class="text-right">Period out</th><th class="text-right">Period tgt</th></tr>
        @foreach($data['production']['job_rows'] ?? [] as $prow)
            @foreach($prow['lines'] as $line)
                <tr>
                    <td>{{ $prow['job']->job_number }}</td>
                    <td>{{ $line['line_name'] }}</td>
                    <td class="text-right">{{ $line['job_target'] }}</td>
                    <td class="text-right">{{ $line['total_produced'] }}</td>
                    <td class="text-right">{{ $line['period_produced'] }}</td>
                    <td class="text-right">{{ $line['period_target'] }}</td>
                </tr>
            @endforeach
        @endforeach
    </table>

    <h3>Washing</h3>
    <table>
        <tr><th>Type</th><th>GP</th><th>Date</th><th>Job</th><th class="text-right">Sent</th><th class="text-right">Rcvd</th><th>Status</th><th>Factory</th></tr>
        @foreach($data['washing_rows'] ?? [] as $w)
            <tr>
                <td>{{ $w->isReceiptBatch() ? 'Rcpt' : 'GP' }}</td>
                <td>{{ $w->id }}</td>
                <td>{{ $w->created_at->format('Y-m-d') }}</td>
                <td>{{ $w->job?->job_number ?? '' }}</td>
                <td class="text-right">{{ $w->isReceiptBatch() ? '' : $w->quantity_sent }}</td>
                <td class="text-right">{{ $w->isReceiptBatch() ? $w->quantity_sent : ($w->quantity_returned ?? 0) }}</td>
                <td>{{ $w->status }}</td>
                <td>{{ Str::limit($w->washing_factory_name ?? '', 25) }}</td>
            </tr>
        @endforeach
    </table>

    <h3>QC</h3>
    <table>
        <tr><th>Date</th><th>Job</th><th class="text-right">Checked</th><th class="text-right">Good</th><th class="text-right">Damage</th></tr>
        @foreach($data['qc_checks'] ?? [] as $qc)
            <tr>
                <td>{{ $qc->created_at->format('Y-m-d') }}</td>
                <td>{{ $qc->washingTransfer?->job?->job_number ?? '' }}</td>
                <td class="text-right">{{ $qc->total_checked }}</td>
                <td class="text-right">{{ $qc->finished_good_qty }}</td>
                <td class="text-right">{{ $qc->damaged_qty }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
