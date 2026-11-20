{{-- Printable / Excel-friendly statement export (LKR, trusted GL amounts only) --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Statement' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; }
        th { background: #f3f4f6; text-align: left; }
        .num { text-align: right; }
        .section { font-weight: bold; background: #f8fafc; }
        .total { font-weight: bold; background: #eff6ff; }
        .kpi { margin: 0 0 10px; }
        .kpi span { display: inline-block; margin-right: 14px; }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Financial Statement' }}</h1>
    <div class="meta">{{ $subtitle ?? '' }} · LKR · Generated {{ now()->format('Y-m-d H:i') }}</div>

    @if(!empty($kpis))
        <div class="kpi">
            @foreach($kpis as $kpi)
                <span><strong>{{ $kpi['label'] }}:</strong> {{ $kpi['value'] }}</span>
            @endforeach
        </div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach(($columns ?? []) as $col)
                    <th class="{{ preg_match('/amount|total|balance|debit|credit|opening|closing|movement/i', (string) $col) ? 'num' : '' }}">{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach(($rows ?? []) as $row)
                @php
                    $cls = $row['_class'] ?? '';
                    $cells = $row['cells'] ?? $row;
                    if (isset($cells['_class'])) unset($cells['_class']);
                @endphp
                <tr class="{{ $cls }}">
                    @foreach($cells as $key => $cell)
                        @if($key === '_class') @continue @endif
                        @php $isMoney = is_numeric($cell) && ! in_array($key, ['gl_code', 'account_id', 0, 1], true); @endphp
                        <td class="{{ $isMoney || (is_int($key) && $key >= 2 && is_numeric($cell)) ? 'num' : '' }}">
                            {{ is_numeric($cell) && ! in_array($key, ['gl_code', 'account_id'], true) ? number_format((float) $cell, 2) : $cell }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($notes))
        <p style="margin-top:12px;color:#666;font-size:10px;">{{ $notes }}</p>
    @endif
</body>
</html>
