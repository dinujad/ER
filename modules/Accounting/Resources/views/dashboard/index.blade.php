@extends('accounting::layouts.app')
@section('title', 'Accounting Overview')

@section('content')
@php
    $fmt = function ($value) {
        if ($value === null) {
            return 'Not available';
        }
        return 'LKR ' . number_format((float) $value, 2);
    };
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <h2 class="h4 mb-1" style="font-family:'IBM Plex Sans',sans-serif;font-weight:700">Accounting Overview</h2>
        <p class="text-muted mb-0 small">Balances from posted journal entries · currency LKR</p>
    </div>
    <form method="GET" action="{{ url('accounting/dashboard') }}" class="row g-2 align-items-end">
        <div class="col-auto">
            <label class="form-label small mb-0">From</label>
            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start_date }}">
        </div>
        <div class="col-auto">
            <label class="form-label small mb-0">To</label>
            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end_date }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
        </div>
    </form>
</div>

@unless($summary['available'])
    <div class="alert alert-warning border-0 shadow-sm">
        Chart of accounts / journals are not ready yet. Seed COA and post activity, then refresh.
        Quick links:
        <a href="{{ url('accounting/chart_of_account') }}">Chart of Accounts</a> ·
        <a href="{{ url('accounting/journal_entry') }}">Journal Entries</a>
    </div>
@endunless

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="acct-card h-100">
            <div class="acct-card-body">
                <div class="text-muted small text-uppercase">Revenue (period)</div>
                <div class="fs-5 fw-bold mt-1">{{ $fmt($summary['revenue']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="acct-card h-100">
            <div class="acct-card-body">
                <div class="text-muted small text-uppercase">Expenses (period)</div>
                <div class="fs-5 fw-bold mt-1">{{ $fmt($summary['expenses']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="acct-card h-100">
            <div class="acct-card-body">
                <div class="text-muted small text-uppercase">Net profit (period)</div>
                <div class="fs-5 fw-bold mt-1 {{ ($summary['net_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $fmt($summary['net_profit']) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="acct-card h-100">
            <div class="acct-card-body">
                <div class="text-muted small text-uppercase">Cash balance</div>
                <div class="fs-5 fw-bold mt-1">{{ $fmt($summary['cash']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="acct-card h-100">
            <div class="acct-card-body">
                <div class="text-muted small text-uppercase">Bank balance</div>
                <div class="fs-5 fw-bold mt-1">{{ $fmt($summary['bank']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="acct-card h-100">
            <div class="acct-card-body">
                <div class="text-muted small text-uppercase">Accounts receivable</div>
                <div class="fs-5 fw-bold mt-1">{{ $fmt($summary['receivables']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="acct-card h-100">
            <div class="acct-card-body">
                <div class="text-muted small text-uppercase">Accounts payable</div>
                <div class="fs-5 fw-bold mt-1">{{ $fmt($summary['payables']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="acct-card h-100">
            <div class="acct-card-body">
                <div class="text-muted small text-uppercase">Catalogue</div>
                <div class="mt-1"><strong>{{ number_format($counts['accounts']) }}</strong> accounts</div>
                <div class="text-muted small">{{ number_format($counts['journal_lines']) }} journal lines</div>
            </div>
        </div>
    </div>
</div>

@if(!empty($summary['gross_profit']))
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="acct-card h-100"><div class="acct-card-body">
            <div class="text-muted small text-uppercase">Gross profit</div>
            <div class="fs-5 fw-bold mt-1">{{ $fmt($summary['gross_profit'] ?? null) }}</div>
            @if(isset($summary['gross_margin_pct']))<div class="small text-muted">{{ $summary['gross_margin_pct'] }}% margin</div>@endif
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="acct-card h-100"><div class="acct-card-body">
            <div class="text-muted small text-uppercase">Working capital</div>
            <div class="fs-5 fw-bold mt-1">{{ $fmt($summary['working_capital'] ?? null) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="acct-card h-100"><div class="acct-card-body">
            <div class="text-muted small text-uppercase">Current ratio</div>
            <div class="fs-5 fw-bold mt-1">{{ $summary['current_ratio'] ?? 'N/A' }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="acct-card h-100"><div class="acct-card-body">
            <div class="text-muted small text-uppercase">Loan outstanding</div>
            <div class="fs-5 fw-bold mt-1">{{ $fmt($summary['loan_outstanding'] ?? null) }}</div>
        </div></div>
    </div>
</div>
@if(!empty($summary['chart']))
<div class="acct-card mb-4">
    <div class="acct-card-head"><h6 class="mb-0">Monthly revenue vs expense</h6></div>
    <div class="acct-card-body"><canvas id="acctRevenueExpenseChart" height="100"></canvas></div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const chartData = @json($summary['chart']);
    const el = document.getElementById('acctRevenueExpenseChart');
    if (!el || !chartData.length) return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels: chartData.map(r => r.month),
            datasets: [
                { label: 'Revenue', data: chartData.map(r => r.revenue), backgroundColor: 'rgba(13,110,253,0.6)' },
                { label: 'Expense', data: chartData.map(r => r.expense), backgroundColor: 'rgba(220,53,69,0.55)' }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
})();
</script>
@endpush
@endif
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="acct-card">
            <div class="acct-card-head">
                <h6 class="mb-0">Recent journal lines</h6>
                <a href="{{ url('accounting/journal_entry') }}" class="btn btn-sm btn-outline-secondary">Open journals</a>
            </div>
            <div class="table-responsive">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Txn</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary['recent'] as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td><span class="txn-num">{{ $row['transaction_number'] }}</span></td>
                                <td>{{ $row['account_name'] ?: '—' }}</td>
                                <td>{{ $row['name'] ?: ($row['transaction_type'] ?: '—') }}</td>
                                <td class="text-end">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '—' }}</td>
                                <td class="text-end">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No journal lines yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="acct-card">
            <div class="acct-card-head"><h6 class="mb-0">Quick reports</h6></div>
            <div class="acct-card-body d-grid gap-2">
                <a class="btn btn-outline-primary text-start" href="{{ url('report/accounting/profit_and_loss') }}"><i class="bi bi-graph-up me-2"></i>Profit &amp; Loss</a>
                <a class="btn btn-outline-primary text-start" href="{{ url('report/accounting/balance_sheet') }}"><i class="bi bi-pie-chart me-2"></i>Balance Sheet</a>
                <a class="btn btn-outline-primary text-start" href="{{ url('report/accounting/trial_balance') }}"><i class="bi bi-list-check me-2"></i>Trial Balance</a>
                <a class="btn btn-outline-primary text-start" href="{{ url('report/accounting/accounts_receivable_ageing_summary') }}"><i class="bi bi-people me-2"></i>AR Ageing</a>
                <a class="btn btn-outline-primary text-start" href="{{ url('report/accounting/accounts_payable_ageing_summary') }}"><i class="bi bi-building me-2"></i>AP Ageing</a>
                <a class="btn btn-outline-secondary text-start" href="{{ url('accounting/transactions/sales') }}"><i class="bi bi-arrow-left-right me-2"></i>ERP Transactions</a>
            </div>
        </div>
    </div>
</div>
@endsection
