@extends('accounting::phase2._page')
@php
    $pageTitle = 'Balance Sheet';
    $pageSub = 'As of '.$asOf;
    $assets = (float) ($report['assets'] ?? 0);
    $liabEq = (float) ($report['liabilities_plus_equity'] ?? 0);
    $earnings = (float) ($report['current_earnings'] ?? 0);
    $diff = round($assets - $liabEq, 2);
    $q = http_build_query(['as_of' => $asOf]);
    $base = url('accounting/phase4/statements/balance-sheet');
    $sections = [
        'asset' => ['label' => 'Assets', 'tone' => 'pos'],
        'liability' => ['label' => 'Liabilities', 'tone' => 'neg'],
        'equity' => ['label' => 'Equity', 'tone' => ''],
    ];
    $kpis = [
        ['label' => 'Total Assets', 'value' => 'LKR '.number_format($assets, 2), 'tone' => 'positive', 'meta' => 'As of '.$asOf],
        ['label' => 'Liabilities + Equity', 'value' => 'LKR '.number_format($liabEq, 2), 'meta' => 'Incl. current earnings'],
        ['label' => 'Current Earnings', 'value' => 'LKR '.number_format($earnings, 2), 'tone' => $earnings >= 0 ? 'positive' : 'negative', 'meta' => 'YTD in equity'],
        ['label' => 'Balance Check', 'value' => abs($diff) < 0.005 ? 'Balanced' : 'LKR '.number_format($diff, 2), 'tone' => abs($diff) < 0.005 ? 'positive' : 'negative', 'meta' => 'Assets − (Liab+Equity)'],
    ];
    $chartLabels = ['Assets', 'Liabilities', 'Equity'];
    $chartValues = [
        (float) ($report['totals']['asset'] ?? 0),
        (float) ($report['totals']['liability'] ?? 0),
        (float) ($report['totals']['equity'] ?? 0),
    ];
    $chartColors = ['#2563eb', '#dc2626', '#059669'];
@endphp

@section('page_body')
<x-accounting::advanced_report
    title="Balance Sheet"
    :subtitle="$pageSub"
    :as-of="$asOf"
    filter-action="{{ $base }}"
    chart-id="bsAdvChart"
    chart-type="doughnut"
    :chart-labels="$chartLabels"
    :chart-values="$chartValues"
    :chart-colors="$chartColors"
    export-pdf="{{ $base }}/pdf?{{ $q }}"
    export-xlsx="{{ $base }}/xlsx?{{ $q }}"
    insights-url="{{ url('accounting/phase5/insights') }}"
    qa-url="{{ url('accounting/phase5/assistant') }}"
    :kpis="$kpis"
>
    <x-slot:table>
        @php $hasRows = collect($report['sections'] ?? [])->flatten(1)->isNotEmpty(); @endphp
        @unless($hasRows)
            @include('accounting::components.empty_state', [
                'title' => 'No balances',
                'message' => 'No posted account balances through this date.',
                'icon' => 'bi-pie-chart',
            ])
        @else
            <table class="table acct-fin-table mb-0">
                <thead>
                    <tr>
                        <th style="width:45%">Account</th>
                        <th>GL</th>
                        <th class="text-end">Balance (LKR)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sections as $key => $meta)
                        <tr class="acct-adv-section-total">
                            <td colspan="4">
                                <button type="button" class="acct-adv-group-btn" data-adv-group-toggle="{{ $key }}">
                                    <i class="bi bi-caret-down-fill"></i> {{ $meta['label'] }}
                                </button>
                            </td>
                        </tr>
                        @foreach(($report['sections'][$key] ?? []) as $line)
                            <tr data-group="{{ $key }}" data-amount="{{ $line['balance'] }}">
                                <td>{{ $line['name'] }}</td>
                                <td><span class="gl-badge">{{ $line['gl_code'] }}</span></td>
                                <td class="acct-amt {{ $meta['tone'] }}" data-adv-money="{{ $line['balance'] }}">LKR {{ number_format((float) $line['balance'], 2) }}</td>
                                <td class="text-end">
                                    @if(!empty($line['account_id']))
                                        <a class="btn btn-sm btn-link" href="{{ url('accounting/account-register/'.$line['account_id'].'?to='.$asOf) }}">Drill down</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr class="acct-adv-section-total" data-amount="{{ $report['totals'][$key] ?? 0 }}">
                            <td colspan="2">Total {{ $meta['label'] }}</td>
                            <td class="acct-amt" data-adv-money="{{ $report['totals'][$key] ?? 0 }}">LKR {{ number_format((float) ($report['totals'][$key] ?? 0), 2) }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                    <tr class="acct-adv-section-total" data-amount="{{ $earnings }}">
                        <td colspan="2">Current-period earnings (included in equity)</td>
                        <td class="acct-amt" data-adv-money="{{ $earnings }}">LKR {{ number_format($earnings, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        @endunless
    </x-slot:table>
</x-accounting::advanced_report>
@endsection
