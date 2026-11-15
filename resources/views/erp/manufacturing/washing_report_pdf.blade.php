<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Washing Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; }
        h2 { font-size: 14px; margin: 0 0 4px; color: #0c4a6e; }
        .meta { font-size: 9px; color: #555; margin-bottom: 10px; }
        .summary { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .summary td { border: 1px solid #cbd5e1; padding: 5px 8px; text-align: center; }
        .summary .lbl { font-size: 7px; text-transform: uppercase; color: #64748b; }
        .summary .num { font-size: 12px; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; vertical-align: top; }
        table.data th { background: #f1f5f9; font-size: 7px; text-transform: uppercase; }
        table.data tr.receipt td { background: #f8fafc; }
        .text-right { text-align: right; }
        .badge { font-size: 7px; font-weight: bold; }
    </style>
</head>
<body>
    <h2>{{ config('app.name') }} — Washing Report</h2>
    <div class="meta">
        Period: {{ \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') }}
        — {{ \Carbon\Carbon::parse($filters['date_to'])->format('d M Y') }}
        | Generated: {{ now()->format('d M Y H:i') }}
        | Records: {{ $rows->count() }}
    </div>

    <table class="summary">
        <tr>
            <td><div class="lbl">Gate passes</div><div class="num">{{ $summary['gate_pass_count'] }}</div></td>
            <td><div class="lbl">Receipt batches</div><div class="num">{{ $summary['receipt_count'] }}</div></td>
            <td><div class="lbl">Total sent (pcs)</div><div class="num">{{ number_format($summary['total_sent']) }}</div></td>
            <td><div class="lbl">Total received</div><div class="num">{{ number_format($summary['total_returned']) }}</div></td>
            <td><div class="lbl">Pending at wash</div><div class="num">{{ number_format($summary['pending_at_wash']) }}</div></td>
        </tr>
    </table>

    @php
        $filterParts = [];
        if (($filters['record_type'] ?? 'all') !== 'all') $filterParts[] = 'Type: ' . $filters['record_type'];
        if (($filters['status'] ?? 'all') !== 'all') $filterParts[] = 'Status: ' . ($options['statuses'][$filters['status']] ?? $filters['status']);
        if (!empty($filters['job_id'])) {
            $j = $options['jobs']->firstWhere('id', (int) $filters['job_id']);
            $filterParts[] = 'Job: ' . ($j->job_number ?? $filters['job_id']);
        }
        if (($filters['washing_type'] ?? 'all') !== 'all') $filterParts[] = 'Wash: ' . ($options['washing_types'][$filters['washing_type']] ?? '');
        if (!empty($filters['washing_factory'])) $filterParts[] = 'Factory: ' . $filters['washing_factory'];
        if (!empty($filters['vehicle_number'])) $filterParts[] = 'Vehicle: ' . $filters['vehicle_number'];
        if (!empty($filters['sent_from'])) $filterParts[] = 'From: ' . $filters['sent_from'];
        if (!empty($filters['search'])) $filterParts[] = 'Search: ' . $filters['search'];
    @endphp
    @if(count($filterParts))
        <div class="meta" style="margin-bottom:8px;"><strong>Applied filters:</strong> {{ implode(' | ', $filterParts) }}</div>
    @endif

    <table class="data">
        <thead>
            <tr>
                <th>Type</th>
                <th>ID</th>
                <th>Date</th>
                <th>Job</th>
                <th>Style</th>
                <th>Product</th>
                <th>Queue</th>
                <th class="text-right">Sent</th>
                <th class="text-right">Rcvd</th>
                <th class="text-right">Pend</th>
                <th>Status</th>
                <th>Wash Type</th>
                <th>Vehicle</th>
                <th>Factory</th>
                <th>Sent From</th>
                <th>Signatures</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @php
                    $isReceipt = $row->isReceiptBatch();
                    $sent = $isReceipt ? 0 : (int) $row->quantity_sent;
                    $received = $isReceipt ? (int) $row->quantity_sent : (int) ($row->quantity_returned ?? 0);
                    $pending = $isReceipt ? 0 : $row->pendingAtWashQuantity();
                    $sigs = collect([$row->admin_signatory, $row->driver_signatory, $row->factory_signatory])->filter()->implode(' / ');
                @endphp
                <tr class="{{ $isReceipt ? 'receipt' : '' }}">
                    <td>{{ $isReceipt ? 'Receipt' : 'Gate Pass' }}</td>
                    <td>GP-{{ $row->id }}@if($row->parent_id)<br><small>P:{{ $row->parent_id }}</small>@endif</td>
                    <td>{{ $row->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $row->job?->job_number ?? '—' }}</td>
                    <td>{{ $row->job?->style_ref ?? '—' }}</td>
                    <td>{{ $row->job?->product?->name ?? '—' }}</td>
                    <td>{{ $row->batch_no ?? '—' }}</td>
                    <td class="text-right">{{ $isReceipt ? '—' : number_format($sent) }}</td>
                    <td class="text-right">{{ number_format($received) }}</td>
                    <td class="text-right">{{ $isReceipt ? '—' : number_format($pending) }}</td>
                    <td>{{ $row->status_label }}</td>
                    <td>{{ $row->washing_type ? $row->washing_type_label : '—' }}</td>
                    <td>{{ $row->vehicle_number ?? '—' }}</td>
                    <td>{{ $row->washing_factory_name ?? '—' }}</td>
                    <td>{{ Str::limit($row->sent_from, 35) }}</td>
                    <td>{{ Str::limit($sigs ?: '—', 40) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
