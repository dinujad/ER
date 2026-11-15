@extends('erp.layout')

@section('title', $title)

@section('content')
<style>
@media print {
    .sidebar, .erp-mobile-header, .no-print, .btn, nav { display: none !important; }
    .content { padding: 0 !important; }
    body { background: #fff !important; }
}
</style>
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>{{ $title }}</h1>
            <p class="text-muted small mb-0">
                Field expenses submitted by sales persons — {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
                to {{ \Carbon\Carbon::parse($to)->format('d M Y') }}.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary">← Reports</a>
            <a href="{{ route('field-expenses.sales-person.index') }}" class="btn btn-sm btn-outline-primary">Expense list</a>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
            @php
                $exportQuery = array_filter([
                    'from' => $from,
                    'to' => $to,
                    'sales_person_id' => $data['filters']['sales_person_id'] ?? null,
                    'type' => $data['filters']['type'] ?? null,
                    'has_slip' => $data['filters']['has_slip'] ?? null,
                    'route_id' => $data['filters']['route_id'] ?? null,
                ]);
            @endphp
            <a href="{{ route('reports.export', array_merge(['report' => 'sales-person-expenses', 'format' => 'pdf'], $exportQuery)) }}" class="btn btn-sm btn-outline-danger">PDF</a>
            <a href="{{ route('reports.export', array_merge(['report' => 'sales-person-expenses', 'format' => 'csv'], $exportQuery)) }}" class="btn btn-sm btn-outline-success">CSV</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small text-uppercase">Total expenses</div>
                    <div class="fs-5 fw-bold text-primary">Rs {{ number_format($data['total'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small text-uppercase">Lines</div>
                    <div class="fs-4 fw-bold">{{ $data['count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small text-uppercase">Sales persons</div>
                    <div class="fs-4 fw-bold">{{ $data['personCount'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small text-uppercase">With receipt</div>
                    <div class="fs-4 fw-bold text-success">{{ $data['withSlipCount'] }}</div>
                    <div class="small text-muted">{{ max(0, $data['count'] - $data['withSlipCount']) }} without</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.show', 'sales-person-expenses') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Sales person</label>
                    <select name="sales_person_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($data['salesPersons'] as $sp)
                            <option value="{{ $sp->id }}" @selected((string) ($data['filters']['sales_person_id'] ?? '') === (string) $sp->id)>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All types</option>
                        @foreach(\App\Models\SalesPersonExpense::typeOptions() as $key => $label)
                            <option value="{{ $key }}" @selected(($data['filters']['type'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Route</label>
                    <select name="route_id" class="form-select form-select-sm">
                        <option value="">All routes</option>
                        @foreach($data['routes'] as $route)
                            <option value="{{ $route->id }}" @selected((string) ($data['filters']['route_id'] ?? '') === (string) $route->id)>{{ $route->pathLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Receipt</label>
                    <select name="has_slip" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="yes" @selected(($data['filters']['has_slip'] ?? '') === 'yes')>With receipt</option>
                        <option value="no" @selected(($data['filters']['has_slip'] ?? '') === 'no')>No receipt</option>
                    </select>
                </div>
                <div class="col-md-12 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="{{ route('reports.show', 'sales-person-expenses') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">By sales person</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sales person</th>
                                <th class="text-end">Lines</th>
                                <th class="text-end">With receipt</th>
                                <th class="text-end">Total (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['byPerson'] as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row->name }}</td>
                                    <td class="text-end">{{ $row->count }}</td>
                                    <td class="text-end">{{ $row->with_slip }}</td>
                                    <td class="text-end font-monospace">{{ number_format($row->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No expenses in this period.</td></tr>
                            @endforelse
                        </tbody>
                        @if($data['byPerson']->isNotEmpty())
                            <tfoot class="table-light">
                                <tr>
                                    <th>Total</th>
                                    <th class="text-end">{{ $data['count'] }}</th>
                                    <th class="text-end">{{ $data['withSlipCount'] }}</th>
                                    <th class="text-end">{{ number_format($data['total'], 2) }}</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">By type</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th class="text-end">Lines</th>
                                <th class="text-end">Total (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['byType'] as $row)
                                <tr>
                                    <td>{{ $row->label }}</td>
                                    <td class="text-end">{{ $row->count }}</td>
                                    <td class="text-end font-monospace">{{ number_format($row->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">No expenses in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-semibold">Expense lines</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Sales person</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Route</th>
                        <th class="text-end">Amount (Rs)</th>
                        <th class="no-print">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['expenses'] as $expense)
                        <tr>
                            <td class="text-nowrap">{{ $expense->expense_date->format('d M Y') }}</td>
                            <td>{{ $expense->user?->name ?? '—' }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $expense->typeLabel() }}</span></td>
                            <td>
                                {{ $expense->description }}
                                @if($expense->notes)
                                    <div class="small text-muted">{{ $expense->notes }}</div>
                                @endif
                            </td>
                            <td class="small">
                                @if($expense->routeRun?->salesRoute)
                                    {{ $expense->routeRun->salesRoute->pathLabel() }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end font-monospace">{{ number_format((float) $expense->amount, 2) }}</td>
                            <td class="no-print">@include('partials.expense-receipt-link', ['expense' => $expense])</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No sales person expenses for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
