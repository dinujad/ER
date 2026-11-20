@extends('accounting::phase2._page')
@php
    $pageTitle = 'Changes in Equity';
    $pageSub = $from.' → '.$to;
    $opening = (float) ($report['opening_equity'] ?? 0);
    $closing = (float) ($report['closing_equity'] ?? 0);
    $earnings = (float) ($report['current_period_earnings'] ?? 0);
    $q = http_build_query(['from' => $from, 'to' => $to]);
    $base = url('accounting/phase4/statements/changes-in-equity');
    $kpis = [
        ['label' => 'Opening Equity', 'value' => 'LKR '.number_format($opening, 2)],
        ['label' => 'Period Earnings', 'value' => 'LKR '.number_format($earnings, 2), 'tone' => $earnings >= 0 ? 'positive' : 'negative'],
        ['label' => 'Closing Equity', 'value' => 'LKR '.number_format($closing, 2), 'tone' => 'positive', 'meta' => 'Incl. current earnings'],
        ['label' => 'Equity accounts', 'value' => (string) count($report['lines'] ?? []), 'meta' => 'Active equity GLs'],
    ];
    $chartLabels = ['Opening', 'Current earnings', 'Closing'];
    $chartValues = [$opening, $earnings, $closing];
    $chartColors = ['#2563eb', '#059669', '#7c3aed'];
@endphp

@section('page_body')
<x-accounting::advanced_report
    title="Statement of Changes in Equity"
    :subtitle="$pageSub"
    :from="$from"
    :to="$to"
    filter-action="{{ $base }}"
    chart-id="eqAdvChart"
    chart-type="bar"
    :chart-labels="$chartLabels"
    :chart-values="$chartValues"
    :chart-colors="$chartColors"
    export-pdf="{{ $base }}/pdf?{{ $q }}"
    export-xlsx="{{ $base }}/xlsx?{{ $q }}"
    insights-url="{{ url('accounting/phase5/insights?from='.$from.'&to='.$to) }}"
    qa-url="{{ url('accounting/phase5/assistant') }}"
    :kpis="$kpis"
>
    <x-slot:table>
        @if(empty($report['lines']))
            @include('accounting::components.empty_state', [
                'title' => 'No equity accounts',
                'message' => 'No active equity accounts found on the chart of accounts.',
                'icon' => 'bi-diagram-3',
            ])
        @else
            <table class="table acct-fin-table mb-0">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>GL</th>
                        <th class="text-end">Opening</th>
                        <th class="text-end">Movement</th>
                        <th class="text-end">Closing</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['lines'] as $line)
                        <tr data-amount="{{ $line['closing'] }}">
                            <td>{{ $line['name'] }}</td>
                            <td><span class="gl-badge">{{ $line['gl_code'] }}</span></td>
                            <td class="acct-amt" data-adv-money="{{ $line['opening'] }}">LKR {{ number_format((float) $line['opening'], 2) }}</td>
                            <td class="acct-amt {{ ((float)$line['movement']) >= 0 ? 'pos' : 'neg' }}" data-adv-money="{{ $line['movement'] }}">LKR {{ number_format((float) $line['movement'], 2) }}</td>
                            <td class="acct-amt" data-adv-money="{{ $line['closing'] }}">LKR {{ number_format((float) $line['closing'], 2) }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-link" href="{{ url('accounting/phase4/statements/profit-and-loss?from='.$from.'&to='.$to) }}">P&amp;L</a>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="acct-adv-section-total" data-amount="{{ $earnings }}">
                        <td colspan="3">Current-period earnings</td>
                        <td class="acct-amt" data-adv-money="{{ $earnings }}">LKR {{ number_format($earnings, 2) }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr class="acct-adv-grand-total" data-amount="{{ $closing }}">
                        <td colspan="4">Closing equity (incl. current earnings)</td>
                        <td class="acct-amt" data-adv-money="{{ $closing }}">LKR {{ number_format($closing, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        @endif
    </x-slot:table>
</x-accounting::advanced_report>
@endsection
