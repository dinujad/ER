@extends('accounting::layouts.app')
@section('title', 'Accounting Dashboard')

@section('css')
    @include('accounting::dashboard.styles')
@endsection

@section('content')
@php
    $fmt = function ($value) {
        if ($value === null) {
            return 'Not available';
        }

        return 'LKR '.number_format((float) $value, 2);
    };
    $net = $summary['net_profit'] ?? null;
    $cash = (float) ($summary['cash'] ?? 0);
    $bank = (float) ($summary['bank'] ?? 0);
    $availableCash = ($summary['available'] ?? false) ? round($cash + $bank, 2) : null;
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $userFirst = explode(' ', trim(auth()->user()->name ?? 'there'))[0] ?? 'there';
@endphp

<div class="acct-dash-header">
    <div class="heading">
        <h1 class="acct-dash-heading">{{ $greeting }}, {{ $userFirst }}</h1>
        <p class="acct-dash-sub">Posted journal balances · {{ $start_date }} → {{ $end_date }} · LKR</p>
    </div>
    <form method="GET" action="{{ url('accounting/dashboard') }}" class="acct-dash-controls mb-0">
        <div>
            <label class="visually-hidden" for="dashFrom">From</label>
            <input type="date" id="dashFrom" name="start_date" class="form-control form-control-sm" value="{{ $start_date }}" title="From">
        </div>
        <div>
            <label class="visually-hidden" for="dashTo">To</label>
            <input type="date" id="dashTo" name="end_date" class="form-control form-control-sm" value="{{ $end_date }}" title="To">
        </div>
        <a href="{{ url('accounting/phase4/statements/profit-and-loss?from='.$start_date.'&to='.$end_date) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-graph-up me-1" aria-hidden="true"></i> Profit &amp; Loss</a>
        <a href="{{ url('report/accounting/profit_and_loss') }}" class="btn btn-sm btn-outline-secondary">Classic P&amp;L</a>
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1" aria-hidden="true"></i> Apply dates</button>
    </form>
</div>

@unless($summary['available'] ?? false)
    <div class="acct-unavailable mb-3" role="status">
        <i class="bi bi-exclamation-triangle"></i>
        <h3>Accounting data not ready</h3>
        <p>Chart of accounts / journals are not available yet. Seed COA and post activity, then refresh.</p>
        <div class="acct-quick-actions justify-content-center">
            <a href="{{ url('accounting/chart_of_account') }}"><i class="bi bi-diagram-3"></i> Chart of Accounts</a>
            <a href="{{ url('accounting/journal_entry') }}"><i class="bi bi-journal-text"></i> Journals</a>
        </div>
    </div>
@endunless

{{-- Hero KPIs (concept layout) — same trusted GL amounts --}}
<div class="acct-kpi-grid acct-dash-hero-kpis" aria-label="Financial highlights">
    <a href="{{ url('accounting/phase4/statements/profit-and-loss?from='.$start_date.'&to='.$end_date) }}" class="acct-kpi positive text-decoration-none">
        <div class="acct-kpi-top">
            <span class="acct-kpi-label">Total revenue</span>
            <div class="acct-kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
        <div class="acct-kpi-value">{{ $fmt($summary['revenue'] ?? null) }}</div>
        <div class="acct-kpi-meta">Period income · posted journals</div>
    </a>
    <div class="acct-kpi negative">
        <div class="acct-kpi-top">
            <span class="acct-kpi-label">Total expenses</span>
            <div class="acct-kpi-icon orange"><i class="bi bi-graph-down-arrow"></i></div>
        </div>
        <div class="acct-kpi-value">{{ $fmt($summary['expenses'] ?? null) }}</div>
        <div class="acct-kpi-meta">Period expenses</div>
    </div>
    <a href="{{ url('accounting/phase4/statements/profit-and-loss?from='.$start_date.'&to='.$end_date) }}" class="acct-kpi {{ ($net ?? 0) >= 0 ? 'positive' : 'negative' }} text-decoration-none">
        <div class="acct-kpi-top">
            <span class="acct-kpi-label">Net profit</span>
            <div class="acct-kpi-icon green"><i class="bi bi-bar-chart"></i></div>
        </div>
        <div class="acct-kpi-value">{{ $fmt($net) }}</div>
        <div class="acct-kpi-meta">@if(isset($summary['net_margin_pct'])){{ $summary['net_margin_pct'] }}% margin @else Period net @endif</div>
    </a>
    <div class="acct-kpi">
        <div class="acct-kpi-top">
            <span class="acct-kpi-label">Available cash</span>
            <div class="acct-kpi-icon purple"><i class="bi bi-wallet2"></i></div>
        </div>
        <div class="acct-kpi-value">{{ $fmt($availableCash) }}</div>
        <div class="acct-kpi-meta">Cash + bank balances</div>
    </div>
</div>

{{-- All remaining KPIs kept (nothing removed) --}}
<div class="acct-kpi-grid" aria-label="Additional balances">
    <div class="acct-kpi">
        <div class="acct-kpi-label">Cash</div>
        <div class="acct-kpi-value">{{ $fmt($summary['cash'] ?? null) }}</div>
        <div class="acct-kpi-meta">Cash GL</div>
    </div>
    <div class="acct-kpi">
        <div class="acct-kpi-label">Bank</div>
        <div class="acct-kpi-value">{{ $fmt($summary['bank'] ?? null) }}</div>
        <div class="acct-kpi-meta">Bank GL</div>
    </div>
    <a href="{{ url('report/accounting/accounts_receivable_ageing_summary') }}" class="acct-kpi text-decoration-none">
        <div class="acct-kpi-label">Receivables</div>
        <div class="acct-kpi-value">{{ $fmt($summary['receivables'] ?? null) }}</div>
        <div class="acct-kpi-meta">AR control</div>
    </a>
    <a href="{{ url('report/accounting/accounts_payable_ageing_summary') }}" class="acct-kpi text-decoration-none">
        <div class="acct-kpi-label">Payables</div>
        <div class="acct-kpi-value">{{ $fmt($summary['payables'] ?? null) }}</div>
        <div class="acct-kpi-meta">AP control</div>
    </a>
    <div class="acct-kpi">
        <div class="acct-kpi-label">Catalogue</div>
        <div class="acct-kpi-value">{{ number_format($counts['accounts'] ?? 0) }} accts</div>
        <div class="acct-kpi-meta">{{ number_format($counts['journal_lines'] ?? 0) }} journal lines</div>
    </div>
    <div class="acct-kpi positive">
        <div class="acct-kpi-label">Gross Profit</div>
        <div class="acct-kpi-value">{{ $fmt($summary['gross_profit'] ?? null) }}</div>
        <div class="acct-kpi-meta">@isset($summary['gross_margin_pct']){{ $summary['gross_margin_pct'] }}% margin @else Revenue − COGS @endisset</div>
    </div>
    <div class="acct-kpi">
        <div class="acct-kpi-label">Working Capital</div>
        <div class="acct-kpi-value">{{ $fmt($summary['working_capital'] ?? null) }}</div>
        <div class="acct-kpi-meta">Current assets − liabilities</div>
    </div>
    <div class="acct-kpi">
        <div class="acct-kpi-label">Current Ratio</div>
        <div class="acct-kpi-value">{{ $summary['current_ratio'] ?? 'N/A' }}</div>
        <div class="acct-kpi-meta">Liquidity</div>
    </div>
    <div class="acct-kpi">
        <div class="acct-kpi-label">Loans Outstanding</div>
        <div class="acct-kpi-value">{{ $fmt($summary['loan_outstanding'] ?? null) }}</div>
        <div class="acct-kpi-meta">Principal</div>
    </div>
    <div class="acct-kpi">
        <div class="acct-kpi-label">Inventory</div>
        <div class="acct-kpi-value">{{ $fmt($summary['inventory'] ?? null) }}</div>
        <div class="acct-kpi-meta">Stock valuation</div>
    </div>
    <div class="acct-kpi">
        <div class="acct-kpi-label">Fixed Assets NBV</div>
        <div class="acct-kpi-value">{{ $fmt($summary['fixed_asset_nbv'] ?? null) }}</div>
        <div class="acct-kpi-meta">Net book value</div>
    </div>
</div>

<section class="acct-dash-middle">
    <article class="acct-panel acct-chart-card mb-0">
        <div class="acct-panel-header">
            <div>
                <h2>Income &amp; expenses</h2>
                <p class="acct-dash-sub mt-1 mb-0">Monthly revenue vs expense from posted journals</p>
            </div>
            <a class="btn btn-sm btn-link" href="{{ url('accounting/phase4/statements/profit-and-loss?from='.$start_date.'&to='.$end_date) }}">View P&amp;L →</a>
        </div>
        <div class="acct-panel-body">
            <div class="acct-dash-legend">
                <span><i style="background:#3563ed"></i>Revenue</span>
                <span><i style="background:#18a57f"></i>Expenses</span>
            </div>
            @if(!empty($summary['chart']))
                <canvas id="acctRevenueExpenseChart" height="120" aria-label="Monthly revenue versus expense chart"></canvas>
            @else
                @include('accounting::components.empty_state', [
                    'title' => 'No chart data',
                    'message' => 'No monthly income/expense activity in this period.',
                    'icon' => 'bi-graph-up',
                ])
            @endif
        </div>
    </article>

    <article class="acct-panel mb-0">
        <div class="acct-panel-header">
            <div>
                <h2>Cash position</h2>
                <p class="acct-dash-sub mt-1 mb-0">Where your money stands (GL balances)</p>
            </div>
            <a class="btn btn-sm btn-link" href="{{ url('accounting/phase4/statements/cash-flow?from='.$start_date.'&to='.$end_date) }}">View report →</a>
        </div>
        <div class="acct-panel-body">
            <div class="acct-dash-cash-total">{{ $fmt($availableCash) }}</div>
            <p class="acct-dash-sub">Combined cash + bank</p>
            <div class="acct-dash-cash-list">
                <div class="acct-dash-cash-row">
                    <div class="acct-dash-cash-left">
                        <div class="acct-dash-round"><i class="bi bi-cash"></i></div>
                        <div><strong>Cash in hand</strong><small>Cash ledger</small></div>
                    </div>
                    <span class="acct-amt">{{ $fmt($summary['cash'] ?? null) }}</span>
                </div>
                <div class="acct-dash-cash-row">
                    <div class="acct-dash-cash-left">
                        <div class="acct-dash-round"><i class="bi bi-bank"></i></div>
                        <div><strong>Bank accounts</strong><small>Bank ledger</small></div>
                    </div>
                    <span class="acct-amt">{{ $fmt($summary['bank'] ?? null) }}</span>
                </div>
                <div class="acct-dash-cash-row">
                    <div class="acct-dash-cash-left">
                        <div class="acct-dash-round"><i class="bi bi-arrow-down-left"></i></div>
                        <div><strong>Receivables</strong><small>Outstanding from customers</small></div>
                    </div>
                    <span class="acct-amt">{{ $fmt($summary['receivables'] ?? null) }}</span>
                </div>
                <div class="acct-dash-cash-row">
                    <div class="acct-dash-cash-left">
                        <div class="acct-dash-round"><i class="bi bi-arrow-up-right"></i></div>
                        <div><strong>Payables</strong><small>Outstanding to suppliers</small></div>
                    </div>
                    <span class="acct-amt neg">{{ $fmt($summary['payables'] ?? null) }}</span>
                </div>
            </div>
        </div>
    </article>
</section>

<section class="acct-dash-bottom">
    <article class="acct-panel mb-0">
        <div class="acct-panel-header">
            <div>
                <h2>Recent accounting activity</h2>
                <p class="acct-dash-sub mt-1 mb-0">Latest posted journal lines</p>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="{{ url('accounting/journal_entry') }}">View all →</a>
        </div>
        <div class="acct-table-wrap border-0 rounded-0">
            <table class="table acct-fin-table mb-0">
                <thead>
                    <tr>
                        <th>Transaction</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Account</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary['recent'] ?? [] as $row)
                        <tr>
                            <td>
                                <strong><a href="{{ url('accounting/journal_entry') }}">{{ $row['transaction_number'] }}</a></strong>
                                <div class="small text-muted">{{ $row['name'] ?: '—' }}</div>
                            </td>
                            <td>{{ $row['transaction_type'] ?: 'Journal' }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['account_name'] ?: '—' }}</td>
                            <td class="acct-amt">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '—' }}</td>
                            <td class="acct-amt">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                @include('accounting::components.empty_state', [
                                    'title' => 'No journal lines yet',
                                    'message' => 'Post activity or open journals to see recent lines here.',
                                    'actionUrl' => url('accounting/journal_entry'),
                                    'actionLabel' => 'Open journals',
                                ])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    <div class="d-flex flex-column gap-3">
        <article class="acct-panel mb-0">
            <div class="acct-panel-header">
                <div>
                    <h2>Quick actions</h2>
                    <p class="acct-dash-sub mt-1 mb-0">Your most-used financial tools</p>
                </div>
            </div>
            <div class="acct-panel-body">
                <div class="acct-dash-actions">
                    <a href="{{ url('accounting/manual-journals/create') }}"><i class="bi bi-plus-square"></i> New journal</a>
                    <a href="{{ url('report/accounting/ledger') }}"><i class="bi bi-book"></i> View ledger</a>
                    <a href="{{ url('accounting/reconcile') }}"><i class="bi bi-bank"></i> Reconcile bank</a>
                    <a href="{{ url('accounting/manual-journals/approvals') }}"><i class="bi bi-check2-square"></i> Approvals</a>
                    <a href="{{ url('accounting/phase4/statements/profit-and-loss') }}"><i class="bi bi-graph-up"></i> Profit &amp; loss</a>
                    <a href="{{ url('accounting/phase5/assistant') }}"><i class="bi bi-stars"></i> AI assistant</a>
                    <a href="{{ url('accounting/phase5/review') }}"><i class="bi bi-clipboard-check"></i> AI review</a>
                    <a href="{{ url('accounting/phase4/custom-reports') }}"><i class="bi bi-bookmark-star"></i> Saved reports</a>
                </div>
            </div>
        </article>

        <article class="acct-panel mb-0">
            <div class="acct-panel-header"><h2>Reports</h2></div>
            <div class="acct-panel-body">
                <div class="acct-dash-reports">
                    <a href="{{ url('accounting/phase4/statements/profit-and-loss') }}"><i class="bi bi-graph-up"></i> Profit &amp; Loss</a>
                    <a href="{{ url('accounting/phase4/statements/balance-sheet') }}"><i class="bi bi-pie-chart"></i> Balance Sheet</a>
                    <a href="{{ url('accounting/phase4/statements/trial-balance') }}"><i class="bi bi-list-check"></i> Trial Balance</a>
                    <a href="{{ url('accounting/phase4/statements/cash-flow') }}"><i class="bi bi-water"></i> Cash Flow</a>
                    <a href="{{ url('accounting/cost-center-reports/profit-and-loss') }}"><i class="bi bi-bar-chart"></i> Cost Center P&amp;L</a>
                    <a href="{{ url('report/accounting/accounts_receivable_ageing_summary') }}"><i class="bi bi-people"></i> AR Ageing</a>
                    <a href="{{ url('report/accounting/accounts_payable_ageing_summary') }}"><i class="bi bi-building"></i> AP Ageing</a>
                    <a href="{{ url('report/accounting/profit_and_loss') }}"><i class="bi bi-file-earmark-text"></i> Classic P&amp;L</a>
                    <a href="{{ url('report/accounting/balance_sheet') }}"><i class="bi bi-file-earmark-text"></i> Classic Balance Sheet</a>
                    <a href="{{ url('report/accounting/trial_balance') }}"><i class="bi bi-file-earmark-text"></i> Classic Trial Balance</a>
                </div>
            </div>
        </article>
    </div>
</section>

<p class="text-muted small mt-3 mb-0 d-flex flex-wrap justify-content-between gap-2">
    <span>E-Media Accounting · amounts from posted journals only</span>
    <span>LKR · {{ $start_date }} → {{ $end_date }}</span>
</p>

@if(!empty($summary['chart']))
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const chartData = @json($summary['chart']);
    const el = document.getElementById('acctRevenueExpenseChart');
    if (!el || !chartData.length || typeof Chart === 'undefined') return;
    new Chart(el, {
        type: 'line',
        data: {
            labels: chartData.map(r => r.month),
            datasets: [
                {
                    label: 'Revenue',
                    data: chartData.map(r => r.revenue),
                    borderColor: '#3563ed',
                    backgroundColor: 'rgba(53,99,237,0.09)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3.5,
                    pointBackgroundColor: '#3563ed',
                    borderWidth: 3
                },
                {
                    label: 'Expense',
                    data: chartData.map(r => r.expense),
                    borderColor: '#18a57f',
                    backgroundColor: 'rgba(24,165,127,0.09)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3.5,
                    pointBackgroundColor: '#18a57f',
                    borderWidth: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: { beginAtZero: true, grid: { color: '#ebeff6' } },
                x: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });
})();
</script>
@endpush
@endif
@endsection
