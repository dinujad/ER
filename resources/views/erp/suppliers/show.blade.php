@extends('erp.layout')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h4 class="mb-0">Supplier: {{ $supplier->name }}</h4>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('purchases.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-primary">New Purchase</a>
        @canRoute('suppliers.edit')
<a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-outline-primary">Edit</a>
@endcanRoute
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">All-time Purchases</div>
                <div class="fs-5 fw-bold">{{ number_format($supplier->total_purchases, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">All-time Paid</div>
                <div class="fs-5 fw-bold text-success">{{ number_format($supplier->total_paid, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Outstanding</div>
                <div class="fs-5 fw-bold {{ $supplier->outstanding_balance > 0 ? 'text-danger' : '' }}">{{ number_format($supplier->outstanding_balance, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-primary">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Period total (filtered)</div>
                <div class="fs-5 fw-bold text-primary">{{ number_format($periodSummary['total'], 2) }}</div>
                <small class="text-muted">{{ $periodSummary['count'] }} purchase(s)</small>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Details</strong></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6"><strong>Code:</strong> {{ $supplier->code }}</div>
            <div class="col-md-6"><strong>Company:</strong> {{ $supplier->company_name ?? '—' }}</div>
            <div class="col-md-6 mt-2"><strong>Phone:</strong> {{ $supplier->phone }}</div>
            <div class="col-md-6 mt-2"><strong>Email:</strong> {{ $supplier->email ?? '—' }}</div>
            <div class="col-12 mt-2"><strong>Address:</strong> {{ $supplier->address ?? '—' }}</div>
            <div class="col-md-6 mt-2"><strong>Tax/VAT No:</strong> {{ $supplier->tax_vat_no ?? '—' }}</div>
            <div class="col-md-6 mt-2"><strong>Status:</strong> <span class="badge bg-{{ $supplier->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($supplier->status) }}</span></div>
        </div>
    </div>
</div>

@php
    $exportBase = [
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ];
@endphp

<form method="GET" action="{{ route('suppliers.show', $supplier) }}" class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <strong>Purchase history — date range</strong>
        <div class="dropdown">
            <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">Export report</button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('suppliers.export-purchases', array_merge(['supplier' => $supplier->id, 'format' => 'csv'], $exportBase)) }}">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i> Excel (CSV)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('suppliers.export-purchases', array_merge(['supplier' => $supplier->id, 'format' => 'pdf'], $exportBase)) }}">
                        <i class="bi bi-file-pdf me-2"></i> PDF
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-0">Date from</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0">Date to</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary btn-sm w-100">Apply filter</button>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">
                    Period: <strong>{{ number_format($periodSummary['total'], 2) }}</strong> purchases —
                    Paid <strong class="text-success">{{ number_format($periodSummary['paid'], 2) }}</strong> —
                    Balance <strong class="{{ $periodSummary['balance'] > 0 ? 'text-danger' : '' }}">{{ number_format($periodSummary['balance'], 2) }}</strong>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Purchases ({{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }})</strong>
                <a href="{{ route('purchases.index', ['supplier_id' => $supplier->id, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'status' => 'confirmed']) }}" class="btn btn-sm btn-outline-primary">Open in Purchases</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Purchase No</th>
                                <th>Date</th>
                                <th>Ref</th>
                                <th>Type</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $p)
                            <tr>
                                <td>{{ $p->purchase_no }}</td>
                                <td>{{ $p->date->format('d M Y') }}</td>
                                <td>{{ $p->reference_no ?? '—' }}</td>
                                <td><span class="badge bg-light text-dark">{{ $p->purchase_type_label }}</span></td>
                                <td>{{ number_format($p->total, 2) }}</td>
                                <td>{{ number_format($p->total_paid, 2) }}</td>
                                <td class="{{ $p->balance > 0 ? 'text-danger' : '' }}">{{ number_format($p->balance, 2) }}</td>
                                <td>
                                    <a href="{{ route('purchases.show', $p) }}" class="btn btn-sm btn-link">View</a>
                                    <a href="{{ route('purchases.report', $p) }}" class="btn btn-sm btn-link" target="_blank" title="Print"><i class="bi bi-printer"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No confirmed purchases in this date range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($purchases->hasPages())
                <div class="card-footer bg-white border-0">{{ $purchases->appends(request()->query())->links() }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><strong>Payments in period</strong></div>
            <div class="card-body p-0">
                <div class="px-3 py-2 border-bottom bg-light small">
                    Total paid in period: <strong>{{ number_format($periodPaymentsTotal, 2) }}</strong>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($periodPayments as $pay)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span>{{ $pay->payment_date->format('d M Y') }}</span>
                            <strong>{{ number_format($pay->amount, 2) }}</strong>
                        </div>
                        <small class="text-muted">
                            {{ ucfirst(str_replace('_', ' ', $pay->payment_method ?? '')) }}
                            @if($pay->purchase)
                                — <a href="{{ route('purchases.show', $pay->purchase) }}">{{ $pay->purchase->purchase_no }}</a>
                            @endif
                        </small>
                    </li>
                    @empty
                    <li class="list-group-item text-muted text-center">No payments in this period.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
