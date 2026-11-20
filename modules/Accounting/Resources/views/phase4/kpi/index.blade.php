@extends('accounting::phase2._page')
@php
    $pageTitle = 'KPI Analytics';
    $pageSub = $from.' → '.$to;
    $gross = $kpis['gross_margin_pct'] ?? null;
    $current = $kpis['current_ratio'] ?? null;
    $quick = $kpis['quick_ratio'] ?? null;
    $kpiCards = [
        ['label' => 'Gross Margin', 'value' => $gross === null ? '—' : number_format((float) $gross, 2).'%', 'meta' => 'Income − COGS proxy'],
        ['label' => 'Current Ratio', 'value' => $current === null ? '—' : number_format((float) $current, 2), 'meta' => 'Current assets ÷ liabilities'],
        ['label' => 'Quick Ratio', 'value' => $quick === null ? '—' : number_format((float) $quick, 2), 'meta' => 'Liquidity (no inventory proxy)'],
        ['label' => 'Currency', 'value' => 'LKR', 'meta' => 'Single-ledger'],
    ];
    $chartLabels = ['Gross margin %', 'Current ratio', 'Quick ratio'];
    $chartValues = [(float) ($gross ?? 0), (float) ($current ?? 0), (float) ($quick ?? 0)];
    $chartColors = ['#2563eb', '#059669', '#d97706'];
    $base = url('accounting/phase4/kpi-analytics');
@endphp

@section('page_body')
<x-accounting::advanced_report
    title="Financial KPI Analytics"
    :subtitle="$pageSub"
    :from="$from"
    :to="$to"
    filter-action="{{ $base }}"
    chart-id="kpiAdvChart"
    chart-type="bar"
    :chart-labels="$chartLabels"
    :chart-values="$chartValues"
    :chart-colors="$chartColors"
    :show-customize="false"
    :kpis="$kpiCards"
>
    <x-slot:table>
        <table class="table acct-fin-table mb-0">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr data-amount="{{ $gross ?? 0 }}">
                    <td>Gross margin %</td>
                    <td class="acct-amt">{{ $gross === null ? '—' : number_format((float) $gross, 2).'%' }}</td>
                    <td class="small text-muted">Derived from posted income/expense GLs</td>
                    <td class="text-end"><a class="btn btn-sm btn-link" href="{{ url('accounting/phase4/statements/profit-and-loss?from='.$from.'&to='.$to) }}">Drill to P&amp;L</a></td>
                </tr>
                <tr data-amount="{{ $current ?? 0 }}">
                    <td>Current ratio</td>
                    <td class="acct-amt">{{ $current === null ? '—' : number_format((float) $current, 2) }}</td>
                    <td class="small text-muted">Balance-sheet liquidity</td>
                    <td class="text-end"><a class="btn btn-sm btn-link" href="{{ url('accounting/phase4/statements/balance-sheet?as_of='.$to) }}">Drill to Balance Sheet</a></td>
                </tr>
                <tr data-amount="{{ $quick ?? 0 }}">
                    <td>Quick ratio</td>
                    <td class="acct-amt">{{ $quick === null ? '—' : number_format((float) $quick, 2) }}</td>
                    <td class="small text-muted">More conservative liquidity</td>
                    <td class="text-end"><a class="btn btn-sm btn-link" href="{{ url('accounting/phase4/statements/balance-sheet?as_of='.$to) }}">Drill to Balance Sheet</a></td>
                </tr>
            </tbody>
        </table>
        @if(!empty($kpis['meta']) && is_array($kpis['meta']))
            <div class="p-3 small text-muted border-top">
                @foreach($kpis['meta'] as $k => $v)
                    @if(is_string($v))<div><strong>{{ $k }}:</strong> {{ $v }}</div>@endif
                @endforeach
            </div>
        @endif
    </x-slot:table>
</x-accounting::advanced_report>
@endsection
