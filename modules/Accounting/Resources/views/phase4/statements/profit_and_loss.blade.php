@extends('accounting::phase2._page')
@php
    $pageTitle = 'Profit and Loss';
    $pageSub = $from.' → '.$to;
    $income = (float) ($report['income'] ?? 0);
    $expense = (float) ($report['expense'] ?? 0);
    $net = (float) ($report['net'] ?? 0);
    $margin = $income > 0.0001 ? round(($net / $income) * 100, 2) : null;
    $incomeLines = collect($report['lines'] ?? [])->where('account_type', 'income')->values();
    $expenseLines = collect($report['lines'] ?? [])->where('account_type', 'expense')->values();
    $q = http_build_query(array_filter([
        'from' => $from,
        'to' => $to,
        'compare_from' => $compareFrom ?? null,
        'compare_to' => $compareTo ?? null,
    ]));
    $base = url('accounting/phase4/statements/profit-and-loss');
    $kpis = [
        ['label' => 'Income', 'value' => 'LKR '.number_format($income, 2), 'tone' => 'positive', 'meta' => $from.' → '.$to],
        ['label' => 'Expenses', 'value' => 'LKR '.number_format($expense, 2), 'tone' => 'negative', 'meta' => 'Posted GL'],
        ['label' => 'Net Profit', 'value' => 'LKR '.number_format($net, 2), 'tone' => $net >= 0 ? 'positive' : 'negative', 'meta' => 'Income − Expenses'],
        ['label' => 'Profit Margin', 'value' => $margin === null ? '—' : number_format($margin, 2).'%', 'meta' => 'Net ÷ Income'],
    ];
    $chartLabels = ['Income', 'Expenses'];
    $chartValues = [$income, $expense];
    $chartColors = ['#059669', '#dc2626'];
@endphp

@section('page_body')
<x-accounting::advanced_report
    title="Profit and Loss"
    :subtitle="$pageSub"
    :from="$from"
    :to="$to"
    filter-action="{{ $base }}"
    chart-id="plAdvChart"
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
    <x-slot:filters>
        <div>
            <label class="form-label">From</label>
            <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm" required>
        </div>
        <div>
            <label class="form-label">To</label>
            <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm" required>
        </div>
        <div>
            <label class="form-label">Compare from</label>
            <input type="date" name="compare_from" value="{{ $compareFrom }}" class="form-control form-control-sm">
        </div>
        <div>
            <label class="form-label">Compare to</label>
            <input type="date" name="compare_to" value="{{ $compareTo }}" class="form-control form-control-sm">
        </div>
    </x-slot:filters>

    <x-slot:table>
        @if(empty($report['lines']))
            @include('accounting::components.empty_state', [
                'title' => 'No P&L activity',
                'message' => 'No income or expense journals were found for this period.',
                'icon' => 'bi-graph-up',
            ])
        @else
            <table class="table acct-fin-table mb-0">
                <thead>
                    <tr>
                        <th style="width:40%">Account</th>
                        <th>GL</th>
                        <th>Type</th>
                        <th class="text-end">Amount (LKR)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="acct-adv-section-total">
                        <td colspan="5">
                            <button type="button" class="acct-adv-group-btn" data-adv-group-toggle="income">
                                <i class="bi bi-caret-down-fill"></i> Income
                            </button>
                        </td>
                    </tr>
                    @foreach($incomeLines as $line)
                        <tr data-group="income" data-amount="{{ $line['amount'] }}">
                            <td>{{ $line['name'] }}</td>
                            <td><span class="gl-badge">{{ $line['gl_code'] }}</span></td>
                            <td><span class="chip chip-income">income</span></td>
                            <td class="acct-amt pos" data-adv-money="{{ $line['amount'] }}">LKR {{ number_format((float) $line['amount'], 2) }}</td>
                            <td class="text-end">
                                @if(!empty($line['account_id']))
                                    <a class="btn btn-xs btn-outline-primary" title="Every transaction on this account, with its source document" href="{{ url('accounting/account-register/'.$line['account_id'].'?from='.$from.'&to='.$to) }}"><i class="bi bi-zoom-in"></i> Drill down</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr class="acct-adv-section-total" data-amount="{{ $income }}">
                        <td colspan="3">Total Income</td>
                        <td class="acct-amt pos" data-adv-money="{{ $income }}">LKR {{ number_format($income, 2) }}</td>
                        <td></td>
                    </tr>

                    <tr class="acct-adv-section-total">
                        <td colspan="5">
                            <button type="button" class="acct-adv-group-btn" data-adv-group-toggle="expense">
                                <i class="bi bi-caret-down-fill"></i> Expenses
                            </button>
                        </td>
                    </tr>
                    @foreach($expenseLines as $line)
                        <tr data-group="expense" data-amount="{{ $line['amount'] }}">
                            <td>{{ $line['name'] }}</td>
                            <td><span class="gl-badge">{{ $line['gl_code'] }}</span></td>
                            <td><span class="chip chip-expense">expense</span></td>
                            <td class="acct-amt neg" data-adv-money="{{ $line['amount'] }}">LKR {{ number_format((float) $line['amount'], 2) }}</td>
                            <td class="text-end">
                                @if(!empty($line['account_id']))
                                    <a class="btn btn-xs btn-outline-primary" title="Every transaction on this account, with its source document" href="{{ url('accounting/account-register/'.$line['account_id'].'?from='.$from.'&to='.$to) }}"><i class="bi bi-zoom-in"></i> Drill down</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr class="acct-adv-section-total" data-amount="{{ $expense }}">
                        <td colspan="3">Total Expenses</td>
                        <td class="acct-amt neg" data-adv-money="{{ $expense }}">LKR {{ number_format($expense, 2) }}</td>
                        <td></td>
                    </tr>
                    <tr class="acct-adv-grand-total" data-amount="{{ $net }}">
                        <td colspan="3">Net Profit / (Loss)</td>
                        <td class="acct-amt {{ $net >= 0 ? 'pos' : 'neg' }}" data-adv-money="{{ $net }}">LKR {{ number_format($net, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        @endif

        @if(!empty($report['compare']))
            <div class="p-3 border-top small">
                <strong>Compare period</strong>
                {{ $report['compare_from'] }} → {{ $report['compare_to'] }}:
                Income LKR {{ number_format((float) $report['compare']['income'], 2) }} ·
                Expense LKR {{ number_format((float) $report['compare']['expense'], 2) }} ·
                Net LKR {{ number_format((float) $report['compare']['net'], 2) }}
            </div>
        @endif
    </x-slot:table>
</x-accounting::advanced_report>
@endsection
