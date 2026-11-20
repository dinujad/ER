@extends('accounting::phase2._page')
@php
    $pageTitle = 'Cash Flow';
    $pageSub = $from.' → '.$to;
    $buckets = $report['buckets'] ?? [];
    $operating = (float) ($buckets['operating'] ?? 0);
    $investing = (float) ($buckets['investing'] ?? 0);
    $financing = (float) ($buckets['financing'] ?? 0);
    $unclassified = (float) ($buckets['unclassified'] ?? 0);
    $net = (float) ($report['net_change'] ?? 0);
    $cashGl = (float) ($report['meta']['cash_gl_change'] ?? 0);
    $variance = (float) ($report['meta']['variance_vs_cash_gl'] ?? 0);
    $q = http_build_query(['from' => $from, 'to' => $to]);
    $base = url('accounting/phase4/statements/cash-flow');
    $byBucket = collect($report['lines'] ?? [])->groupBy('bucket');
    $kpis = [
        ['label' => 'Operating', 'value' => 'LKR '.number_format($operating, 2), 'tone' => $operating >= 0 ? 'positive' : 'negative'],
        ['label' => 'Investing', 'value' => 'LKR '.number_format($investing, 2)],
        ['label' => 'Financing', 'value' => 'LKR '.number_format($financing, 2)],
        ['label' => 'Net vs Cash GL', 'value' => 'LKR '.number_format($variance, 2), 'meta' => 'Indirect '.$net.' · Cash GL '.$cashGl, 'tone' => abs($variance) < 0.005 ? 'positive' : 'negative'],
    ];
    $chartLabels = ['Operating', 'Investing', 'Financing', 'Unclassified'];
    $chartValues = [$operating, $investing, $financing, $unclassified];
    $chartColors = ['#2563eb', '#d97706', '#7c3aed', '#94a3b8'];
@endphp

@section('page_body')
<x-accounting::advanced_report
    title="Cash Flow (Indirect)"
    :subtitle="$pageSub"
    :from="$from"
    :to="$to"
    filter-action="{{ $base }}"
    chart-id="cfAdvChart"
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
    <x-slot:aside>
        <div class="small text-muted">
            {{ $report['meta']['reconciliation_note'] ?? '' }}
        </div>
    </x-slot:aside>

    <x-slot:table>
        @if(empty($report['lines']))
            @include('accounting::components.empty_state', [
                'title' => 'No cash-flow activity',
                'message' => 'No posted journal activity for this period.',
                'icon' => 'bi-water',
            ])
        @else
            <table class="table acct-fin-table mb-0">
                <thead>
                    <tr>
                        <th style="width:45%">Account</th>
                        <th>GL</th>
                        <th>Bucket</th>
                        <th class="text-end">Cash effect (LKR)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(['operating', 'investing', 'financing', 'unclassified'] as $bucket)
                        <tr class="acct-adv-section-total">
                            <td colspan="5">
                                <button type="button" class="acct-adv-group-btn" data-adv-group-toggle="{{ $bucket }}">
                                    <i class="bi bi-caret-down-fill"></i> {{ ucfirst($bucket) }}
                                    <span class="text-muted fw-normal">· LKR {{ number_format((float) ($buckets[$bucket] ?? 0), 2) }}</span>
                                </button>
                            </td>
                        </tr>
                        @foreach(($byBucket[$bucket] ?? []) as $line)
                            <tr data-group="{{ $bucket }}" data-amount="{{ $line['cash_effect'] }}">
                                <td>{{ $line['name'] }}</td>
                                <td><span class="gl-badge">{{ $line['gl_code'] }}</span></td>
                                <td>{{ $line['bucket'] }}</td>
                                <td class="acct-amt {{ ((float)$line['cash_effect']) >= 0 ? 'pos' : 'neg' }}" data-adv-money="{{ $line['cash_effect'] }}">LKR {{ number_format((float) $line['cash_effect'], 2) }}</td>
                                <td class="text-end">
                                    @if(!empty($line['account_id']))
                                        <a class="btn btn-sm btn-link" href="{{ url('accounting/account-register/'.$line['account_id'].'?from='.$from.'&to='.$to) }}">Drill down</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="acct-adv-grand-total" data-amount="{{ $net }}">
                        <td colspan="3">Indirect net change</td>
                        <td class="acct-amt" data-adv-money="{{ $net }}">LKR {{ number_format($net, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        @endif
    </x-slot:table>
</x-accounting::advanced_report>
@endsection
