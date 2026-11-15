@extends('erp.layout')

@section('content')
@php
    $s = $data['sales'] ?? [];
    $p = $data['purchases'] ?? [];
    $e = $data['expenses'] ?? [];
    $prod = $data['production'] ?? [];
    $sum = $data['summary'] ?? [];
@endphp
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
        <div>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left me-1"></i> Reports</a>
            <h4 class="mb-0 fw-bold">{{ $title }}</h4>
            <p class="text-muted small mb-0">Final overview — Sales, Purchases, Expenses, Production costs (Transport, Sewing, Washing)</p>
        </div>
        <div class="dropdown">
            <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">Export</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @php $exportParams = array_merge(request()->query(), ['report' => 'business-summary', 'from' => $from, 'to' => $to]); @endphp
                <li><a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'pdf'])) }}"><i class="bi bi-file-pdf me-2"></i> PDF</a></li>
                <li><a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'csv'])) }}"><i class="bi bi-file-earmark-spreadsheet me-2"></i> CSV</a></li>
            </ul>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.show', 'business-summary') }}" class="card mb-4 no-print">
        <div class="card-body d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label small mb-0">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}" required>
            </div>
            <div>
                <label class="form-label small mb-0">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}" required>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        </div>
    </form>

    <p class="text-muted small">Period: <strong>{{ $from }}</strong> to <strong>{{ $to }}</strong></p>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Total Sales</div>
                    <div class="fs-4 fw-bold text-success">Rs {{ number_format($s['total'] ?? 0, 2) }}</div>
                    <div class="small">{{ $s['orders'] ?? 0 }} orders</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Collected</div>
                    <div class="fs-4 fw-bold">Rs {{ number_format($s['collected'] ?? 0, 2) }}</div>
                    <div class="small text-warning">Due: Rs {{ number_format($s['outstanding'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Purchases</div>
                    <div class="fs-4 fw-bold">Rs {{ number_format($p['total'] ?? 0, 2) }}</div>
                    <div class="small">{{ $p['count'] ?? 0 }} POs</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Estimated Margin</div>
                    <div class="fs-4 fw-bold text-primary">Rs {{ number_format($sum['estimated_margin'] ?? 0, 2) }}</div>
                    <div class="small text-muted">Sales − Purchases − Expenses − Added production</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><strong><i class="bi bi-wallet2 me-2"></i>Expenses</strong> — Rs {{ number_format($e['total'] ?? 0, 2) }}</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @forelse($e['by_category'] ?? [] as $cat => $amt)
                                <tr><td>{{ $cat }}</td><td class="text-end">{{ number_format($amt, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center py-2">No expenses</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><strong><i class="bi bi-gear me-2"></i>Production Costs</strong> — {{ $prod['job_count'] ?? 0 }} jobs</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><td>Total Production Cost</td><td class="text-end fw-semibold">Rs {{ number_format($prod['total_cost'] ?? 0, 2) }}</td></tr>
                            <tr><td class="ps-4 text-muted">↳ Fabric (material issued)</td><td class="text-end">{{ number_format($prod['fabric'] ?? 0, 2) }}</td></tr>
                            <tr><td class="ps-4 text-muted">↳ Accessories</td><td class="text-end">{{ number_format($prod['accessory'] ?? 0, 2) }}</td></tr>
                            <tr class="table-light"><td class="ps-4"><strong>Added costs (total)</strong></td><td class="text-end fw-semibold">{{ number_format($prod['additional'] ?? 0, 2) }}</td></tr>
                            <tr><td class="ps-4 text-warning">↳ Transport</td><td class="text-end text-warning fw-semibold">{{ number_format($prod['transport'] ?? 0, 2) }}</td></tr>
                            <tr><td class="ps-4">↳ Sewing</td><td class="text-end">{{ number_format($prod['sewing'] ?? 0, 2) }}</td></tr>
                            <tr><td class="ps-4">↳ Washing</td><td class="text-end">{{ number_format($prod['washing'] ?? 0, 2) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Quick links — detailed reports</strong></div>
        <div class="card-body d-flex flex-wrap gap-2">
            <a href="{{ route('reports.show', ['report' => 'sale', 'from' => $from, 'to' => $to]) }}" class="btn btn-outline-primary btn-sm">Sale Report</a>
            <a href="{{ route('reports.show', ['report' => 'purchase', 'from' => $from, 'to' => $to]) }}" class="btn btn-outline-primary btn-sm">Purchase Report</a>
            <a href="{{ route('reports.show', ['report' => 'expense', 'from' => $from, 'to' => $to]) }}" class="btn btn-outline-primary btn-sm">Expense Report</a>
            <a href="{{ route('reports.show', ['report' => 'manufacturing', 'from' => $from, 'to' => $to]) }}" class="btn btn-outline-primary btn-sm">Manufacturing Report</a>
            <a href="{{ route('reports.show', ['report' => 'cost-analysis', 'from' => $from, 'to' => $to]) }}" class="btn btn-outline-primary btn-sm">Cost Analysis</a>
        </div>
    </div>
</div>
@endsection
