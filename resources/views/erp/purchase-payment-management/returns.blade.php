@extends('erp.layout')

@section('title', 'Purchase Returns')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-arrow-return-left me-2 text-warning"></i>Purchase Returns</h1>
            <p class="text-muted small mb-0">Items returned to suppliers — stock is reduced from inventory when a return is recorded.</p>
        </div>
        <span class="badge bg-warning-subtle text-dark border px-3 py-2">
            Total returned (filtered): <strong>Rs {{ number_format($totalReturned, 2) }}</strong>
        </span>
    </div>

    @include('erp.purchase-payment-management._subnav')

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('purchase-payment-management.returns') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Return No</label>
                    <input type="text" name="return_no" class="form-control form-control-sm" value="{{ request('return_no') }}" placeholder="PR-…">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">PO No</label>
                    <input type="text" name="purchase_no" class="form-control form-control-sm" value="{{ request('purchase_no') }}" placeholder="PO-…">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Supplier</label>
                    <select name="supplier_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" {{ (string) request('supplier_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">From</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">To</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    <a href="{{ route('purchase-payment-management.returns') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Return No</th>
                        <th>Date</th>
                        <th>PO</th>
                        <th>Supplier</th>
                        <th class="text-end">Amount (Rs)</th>
                        <th>Reason</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $ret)
                        <tr>
                            <td class="fw-semibold">{{ $ret->return_no }}</td>
                            <td class="text-muted small">{{ $ret->return_date->format('Y-m-d') }}</td>
                            <td>
                                @if($ret->purchase)
                                    <a href="{{ route('purchases.show', $ret->purchase) }}">{{ $ret->purchase->purchase_no }}</a>
                                @else — @endif
                            </td>
                            <td>{{ optional($ret->supplier)->name ?? '—' }}</td>
                            <td class="text-end font-monospace">{{ number_format((float) $ret->total, 2) }}</td>
                            <td class="small text-muted">{{ Str::limit($ret->reason ?? '—', 60) }}</td>
                            <td class="text-end">
                                @if($ret->purchase)
                                    <a href="{{ route('purchases.show', $ret->purchase) }}" class="btn btn-sm btn-outline-primary">View PO</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">No returns recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($returns->hasPages())
            <div class="card-footer bg-white border-top-0">{{ $returns->links() }}</div>
        @endif
    </div>
</div>
@endsection
