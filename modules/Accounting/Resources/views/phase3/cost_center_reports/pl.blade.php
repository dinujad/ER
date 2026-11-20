@extends('accounting::phase2._page')
@php
    $pageTitle = 'Cost Center Profit & Loss';
    $pageSub = $scopeLabel.' · '.$from.' → '.$to;
    $incomeLines = collect($report['lines'] ?? [])->where('account_type', 'income')->values();
    $expenseLines = collect($report['lines'] ?? [])->where('account_type', 'expense')->values();
@endphp

@section('page_actions')
    <a class="btn btn-sm btn-outline-secondary" href="{{ url('accounting/cost-center-reports/journals?from='.$from.'&to='.$to.'&cost_center_id='.$ccParam) }}">
        <i class="bi bi-journal-text me-1"></i> Journals
    </a>
    <a class="btn btn-sm btn-outline-secondary" href="{{ url('accounting/cost-center-reports/profit-and-loss/pdf?from='.$from.'&to='.$to.'&cost_center_id='.$ccParam) }}">PDF</a>
    <a class="btn btn-sm btn-outline-secondary" href="{{ url('accounting/cost-center-reports/profit-and-loss/xlsx?from='.$from.'&to='.$to.'&cost_center_id='.$ccParam) }}">XLSX</a>
@endsection

@section('page_body')
<form method="get" action="{{ url('accounting/cost-center-reports/profit-and-loss') }}" class="acct-toolbar" aria-label="Cost center P&L filters">
    <div>
        <label class="form-label" for="ccFrom">From</label>
        <input type="date" id="ccFrom" name="from" value="{{ $from }}" class="form-control form-control-sm" required>
    </div>
    <div>
        <label class="form-label" for="ccTo">To</label>
        <input type="date" id="ccTo" name="to" value="{{ $to }}" class="form-control form-control-sm" required>
    </div>
    <div style="min-width:220px">
        <label class="form-label" for="ccSelect">Cost center</label>
        <select id="ccSelect" name="cost_center_id" class="form-select form-select-sm">
            <option value="all" @selected($ccParam==='all' || $ccParam==='')>All (company-wide)</option>
            <option value="unassigned" @selected($ccParam==='unassigned')>Unassigned only</option>
            @foreach($centers as $cc)
                <option value="{{ $cc->id }}" @selected((string)$ccParam===(string)$cc->id)>{{ $cc->code }} — {{ $cc->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary">Apply</button>
        <a href="{{ url('accounting/cost-center-reports/profit-and-loss') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>

@unless($hasCostCenterColumn)
<div class="acct-unavailable mb-3" role="status">
    <i class="bi bi-exclamation-triangle"></i>
    <h3>Cost center dimension unavailable</h3>
    <p class="mb-0">Journal entries are missing <code>cost_center_id</code>. Company-wide totals still work; tagged cost-center filtering cannot run until the column exists.</p>
</div>
@endunless

<div class="acct-kpi-grid">
    <div class="acct-kpi positive">
        <div class="acct-kpi-label">Income</div>
        <div class="acct-kpi-value">LKR {{ number_format($income, 2) }}</div>
        <div class="acct-kpi-meta">{{ $from }} → {{ $to }}</div>
    </div>
    <div class="acct-kpi negative">
        <div class="acct-kpi-label">Expenses</div>
        <div class="acct-kpi-value">LKR {{ number_format($expense, 2) }}</div>
        <div class="acct-kpi-meta">Posted GL expenses</div>
    </div>
    <div class="acct-kpi {{ $net >= 0 ? 'positive' : 'negative' }}">
        <div class="acct-kpi-label">Net Profit</div>
        <div class="acct-kpi-value">LKR {{ number_format($net, 2) }}</div>
        <div class="acct-kpi-meta">{{ $scopeLabel }}</div>
    </div>
    <div class="acct-kpi">
        <div class="acct-kpi-label">Profit Margin</div>
        <div class="acct-kpi-value">{{ $margin === null ? '—' : number_format($margin, 2).'%' }}</div>
        <div class="acct-kpi-meta">Net ÷ Income</div>
    </div>
</div>

@if($hasLines)
<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="acct-panel acct-chart-card h-100">
            <div class="acct-panel-header"><h2>Income vs Expenses</h2></div>
            <div class="acct-panel-body"><canvas id="ccPlChart" height="220" aria-label="Income versus expenses chart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="acct-panel h-100">
            <div class="acct-panel-header">
                <h2>Account detail</h2>
                <span class="acct-badge info">{{ count($report['lines']) }} accounts</span>
            </div>
            <div class="acct-table-wrap">
                <table class="table acct-fin-table mb-0">
                    <thead>
                        <tr>
                            <th>GL</th>
                            <th>Account</th>
                            <th>Type</th>
                            <th class="text-end">Amount (LKR)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($report['lines'] as $line)
                        <tr>
                            <td><span class="gl-badge">{{ $line['gl_code'] }}</span></td>
                            <td>{{ $line['name'] }}</td>
                            <td><span class="chip chip-{{ $line['account_type'] }}">{{ $line['account_type'] }}</span></td>
                            <td class="acct-amt {{ $line['account_type']==='expense' ? 'neg' : 'pos' }}">{{ number_format((float)$line['amount'], 2) }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-link" href="{{ url('accounting/cost-center-reports/journals?from='.$from.'&to='.$to.'&cost_center_id='.$ccParam) }}">Journals</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3">Net</th>
                            <th class="acct-amt {{ $net >= 0 ? 'pos' : 'neg' }}">{{ number_format($net, 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@else
    @include('accounting::components.empty_state', [
        'title' => 'No posted activity for this filter',
        'message' => $scopeMode === 'center'
            ? 'This cost center has no income/expense journal lines in the selected period. Journals may be unassigned or outside the date range — this is not a blank page error.'
            : ($scopeMode === 'unassigned'
                ? 'No unassigned income/expense journals were found for the selected period.'
                : 'No income/expense journals were found for the selected period.'),
        'icon' => 'bi-graph-up',
        'actionUrl' => url('accounting/cost-center-reports/profit-and-loss?from='.$from.'&to='.$to.'&cost_center_id=all'),
        'actionLabel' => 'View company-wide P&L',
    ])
@endif
@endsection

@push('scripts')
@if($hasLines)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  var el = document.getElementById('ccPlChart');
  if (!el || typeof Chart === 'undefined') return;
  new Chart(el, {
    type: 'doughnut',
    data: {
      labels: ['Income', 'Expenses'],
      datasets: [{
        data: [{{ json_encode(round($income, 2)) }}, {{ json_encode(round($expense, 2)) }}],
        backgroundColor: ['#059669', '#dc2626'],
        borderWidth: 0
      }]
    },
    options: {
      plugins: { legend: { position: 'bottom' } },
      cutout: '62%'
    }
  });
})();
</script>
@endif
@endpush
