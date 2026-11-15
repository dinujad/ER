@extends('erp.layout')

@section('content')
@php
    $rows = $data['rows'] ?? [];
    $sum = $data['summary'] ?? [];
@endphp
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
        <div>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left me-1"></i> Reports</a>
            <h4 class="mb-0 fw-bold">{{ $title }}</h4>
            <p class="text-muted small mb-0">Recipe planned cost vs actual job cost — when product manufacturing is finished</p>
        </div>
        <div class="dropdown">
            <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">Export</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @php $exportParams = ['report' => 'product-final-cost', 'from' => $from, 'to' => $to]; @endphp
                <li><a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'csv'])) }}"><i class="bi bi-file-earmark-spreadsheet me-2"></i> CSV</a></li>
            </ul>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.show', 'product-final-cost') }}" class="card mb-4 no-print">
        <div class="card-body d-flex flex-wrap gap-2 align-items-end">
            <div><label class="form-label small mb-0">From</label><input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}" required></div>
            <div><label class="form-label small mb-0">To</label><input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}" required></div>
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card text-center py-3"><div class="small text-muted">Jobs</div><div class="fs-4 fw-bold">{{ $sum['job_count'] ?? 0 }}</div></div></div>
        <div class="col-md-3"><div class="card text-center py-3"><div class="small text-muted">Planned total</div><div class="fs-5 fw-bold">Rs {{ number_format($sum['planned_total'] ?? 0, 2) }}</div></div></div>
        <div class="col-md-3"><div class="card text-center py-3"><div class="small text-muted">Actual total</div><div class="fs-5 fw-bold text-primary">Rs {{ number_format($sum['actual_total'] ?? 0, 2) }}</div></div></div>
        <div class="col-md-3"><div class="card text-center py-3"><div class="small text-muted">Variance</div><div class="fs-5 fw-bold {{ ($sum['variance_total'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">Rs {{ number_format($sum['variance_total'] ?? 0, 2) }}</div></div></div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>Job #</th>
                    <th>Product</th>
                    <th>Recipe</th>
                    <th class="text-end">Output</th>
                    <th class="text-end">Planned/pc</th>
                    <th class="text-end">Actual/pc</th>
                    <th class="text-end">Variance/pc</th>
                    <th class="text-end">Profit/pc</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $j = $row['job'];
                        $p = $row['planned'] ?? [];
                        $a = $row['actual'] ?? [];
                        $v = $row['variance'] ?? [];
                        $s = $row['selling'] ?? [];
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $j->job_number }}</td>
                        <td class="small">{{ $row['product']?->name ?? '—' }}</td>
                        <td class="small">{{ $p['recipe_name'] ?? '—' }}</td>
                        <td class="text-end">{{ number_format($row['output_qty'] ?? 0) }}</td>
                        <td class="text-end">{{ number_format($p['per_unit'] ?? 0, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($a['per_unit'] ?? 0, 2) }}</td>
                        <td class="text-end {{ ($v['per_unit'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($v['per_unit'] ?? 0, 2) }}</td>
                        <td class="text-end text-success">{{ $s['profit_per_unit_actual'] !== null ? number_format($s['profit_per_unit_actual'], 2) : '—' }}</td>
                        <td>
                            @if($row['is_converted_to_product'] ?? false)
                                <span class="badge bg-success">Converted</span>
                            @else
                                <span class="badge bg-warning text-dark">Final check</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('manufacturing.final.detail', $j->id) }}" class="btn btn-sm btn-link py-0">Final</a>
                            <a href="{{ route('jobs.costs', $j->id) }}" class="btn btn-sm btn-link py-0">Costs</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-muted text-center py-4">No finished / final-check jobs in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="small text-muted mb-0">Extend later: variation-wise costs, batch splits, accounting cost snapshot export.</p>
</div>
@endsection
