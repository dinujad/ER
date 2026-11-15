@extends('erp.layout')

@section('content')
@php
    $rows = $data['rows'] ?? [];
    $sum = $data['summary'] ?? [];
    $filters = $data['filters'] ?? [];
    $margin = $filters['margin_percent'] ?? $defaultMargin;
@endphp
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold"><i class="bi bi-robot text-primary me-2"></i>Cost Analyst</h4>
            <p class="text-muted small mb-0">Completed manufacturing jobs — actual cost, suggested sell price, margin % · Admin only</p>
        </div>
        <a href="{{ route('reports.show', 'cost-analyst') }}" class="btn btn-outline-secondary btn-sm">Expense ledger report</a>
    </div>

    <form method="GET" action="{{ route('cost-analyst.index') }}" class="card mb-4 border-primary">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small mb-0">Target profit margin: <strong id="marginLabel">{{ $margin }}%</strong></label>
                    <input type="range" name="margin" class="form-range" min="5" max="100" step="1" value="{{ $margin }}"
                           oninput="document.getElementById('marginLabel').textContent = this.value + '%'">
                    <small class="text-muted">Suggested sell = cost + this % markup (laba margin)</small>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All finished</option>
                        <option value="converted" @selected(($filters['status'] ?? '') === 'converted')>Converted to product</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card text-center py-3"><div class="small text-muted">Jobs</div><div class="fs-3 fw-bold">{{ $sum['job_count'] ?? 0 }}</div></div></div>
        <div class="col-md-4"><div class="card text-center py-3"><div class="small text-muted">Avg cost / pc</div><div class="fs-5 fw-bold text-primary">Rs {{ number_format($sum['avg_cost_per_unit'] ?? 0, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card text-center py-3 border-success"><div class="small text-muted">Avg suggested sell</div><div class="fs-5 fw-bold text-success">Rs {{ number_format($sum['avg_suggested_price'] ?? 0, 2) }}</div></div></div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Job #</th>
                        <th>Product</th>
                        <th class="text-end">Output</th>
                        <th class="text-end">Cost/pc</th>
                        <th class="text-end">Suggested sell</th>
                        <th class="text-end">Profit/pc</th>
                        <th class="text-end">Past avg sell</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php $j = $row['job']; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $j->job_number }}</td>
                            <td class="small">{{ $row['product']?->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format($row['output_qty'] ?? 0) }}</td>
                            <td class="text-end">Rs {{ number_format($row['actual']['per_unit'] ?? 0, 2) }}</td>
                            <td class="text-end fw-bold text-success">Rs {{ number_format($row['suggested_sell_price'] ?? 0, 2) }}</td>
                            <td class="text-end text-success">Rs {{ number_format($row['suggested_profit_per_unit'] ?? 0, 2) }}</td>
                            <td class="text-end text-muted">{{ $row['historical_avg_sell'] ? 'Rs '.number_format($row['historical_avg_sell'], 2) : '—' }}</td>
                            <td>
                                @if($row['is_converted_to_product'] ?? false)
                                    <span class="badge bg-success">Converted</span>
                                @else
                                    <span class="badge bg-warning text-dark">Final check</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('cost-analyst.show', ['job' => $j->id, 'margin' => $margin]) }}" class="btn btn-sm btn-primary py-0">Analyze</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-muted text-center py-4">No completed jobs in this period. Finish manufacturing → Final Checking.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="small text-muted mb-0">
        Set <code>GEMINI_API_KEY</code> in <code>.env</code> for AI pricing plan on job detail page. Without it, rule-based advisor still works.
    </p>
</div>
@endsection
