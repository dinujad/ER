@extends('accounting::phase2._page')
@php
    $pageTitle = 'Trial Balance';
    $pageSub = $from.' → '.$to;
    $totalDebit = (float) ($report['total_debit'] ?? 0);
    $totalCredit = (float) ($report['total_credit'] ?? 0);
    $diff = round($totalDebit - $totalCredit, 2);
    $q = http_build_query(['from' => $from, 'to' => $to]);
    $base = url('accounting/phase4/statements/trial-balance');
    $kpis = [
        ['label' => 'Total Debit', 'value' => 'LKR '.number_format($totalDebit, 2), 'meta' => $from.' → '.$to],
        ['label' => 'Total Credit', 'value' => 'LKR '.number_format($totalCredit, 2)],
        ['label' => 'Difference', 'value' => abs($diff) < 0.005 ? 'Balanced' : 'LKR '.number_format($diff, 2), 'tone' => abs($diff) < 0.005 ? 'positive' : 'negative'],
        ['label' => 'Accounts', 'value' => (string) count($report['rows'] ?? []), 'meta' => 'With period activity'],
    ];
    $chartLabels = ['Total Debit', 'Total Credit'];
    $chartValues = [$totalDebit, $totalCredit];
    $chartColors = ['#2563eb', '#059669'];
@endphp

@section('page_body')
<x-accounting::advanced_report
    title="Trial Balance"
    :subtitle="$pageSub"
    :from="$from"
    :to="$to"
    filter-action="{{ $base }}"
    chart-id="tbAdvChart"
    chart-type="doughnut"
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
        @if(empty($report['rows']))
            @include('accounting::components.empty_state', [
                'title' => 'No trial balance rows',
                'message' => 'No debit/credit activity for the selected period.',
                'icon' => 'bi-list-check',
            ])
        @else
            <table class="table acct-fin-table mb-0">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>GL</th>
                        <th>Type</th>
                        <th class="text-end">Debit (LKR)</th>
                        <th class="text-end">Credit (LKR)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['rows'] as $row)
                        @php $amt = max((float) $row['debit'], (float) $row['credit']); @endphp
                        <tr data-amount="{{ $amt }}">
                            <td>{{ $row['name'] }}</td>
                            <td><span class="gl-badge">{{ $row['gl_code'] }}</span></td>
                            <td><span class="chip chip-{{ $row['account_type'] }}">{{ $row['account_type'] }}</span></td>
                            <td class="acct-amt" data-adv-money="{{ $row['debit'] }}">{{ ((float)$row['debit']) > 0 ? 'LKR '.number_format((float)$row['debit'], 2) : '—' }}</td>
                            <td class="acct-amt" data-adv-money="{{ $row['credit'] }}">{{ ((float)$row['credit']) > 0 ? 'LKR '.number_format((float)$row['credit'], 2) : '—' }}</td>
                            <td class="text-end">
                                @if(!empty($row['account_id']))
                                    <a class="btn btn-xs btn-outline-primary" title="Every transaction on this account, with its source document" href="{{ url('accounting/account-register/'.$row['account_id'].'?from='.$from.'&to='.$to) }}"><i class="bi bi-zoom-in"></i> Drill down</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr class="acct-adv-grand-total" data-amount="{{ $totalDebit }}">
                        <td colspan="3">Totals</td>
                        <td class="acct-amt" data-adv-money="{{ $totalDebit }}">LKR {{ number_format($totalDebit, 2) }}</td>
                        <td class="acct-amt" data-adv-money="{{ $totalCredit }}">LKR {{ number_format($totalCredit, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        @endif
    </x-slot:table>
</x-accounting::advanced_report>
@endsection
