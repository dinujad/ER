@extends('erp.layout')

@section('title', 'Retail Quotations')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-file-earmark-text me-2 text-info"></i>Retail Quotations</h1>
            <p class="text-muted small mb-0">POS quotations — not sales until opened in POS and paid.</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <span class="badge bg-info-subtle text-dark border px-3 py-2">
                Quoted: <strong>Rs {{ number_format($totalQuoted, 2) }}</strong>
            </span>
            <a href="{{ route('pos.index') }}" class="btn btn-info btn-sm text-white">
                <i class="bi bi-plus-lg me-1"></i> New from POS
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('retail-quotations.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Quotation No</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="QT-…">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Customer</label>
                    <select name="customer_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ (string) request('customer_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->display_name }}</option>
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
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Quotation No</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th class="text-end">Total (Rs)</th>
                        <th>Cashier</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotations as $quote)
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('retail-quotations.show', $quote) }}">{{ $quote->order_number }}</a>
                            </td>
                            <td class="text-muted small">{{ optional($quote->order_date)->format('Y-m-d H:i') }}</td>
                            <td>{{ $quote->customer?->display_name ?? 'Walk-in' }}</td>
                            <td>{{ $quote->items->count() }}</td>
                            <td class="text-end font-monospace">{{ number_format((float) $quote->total_amount, 2) }}</td>
                            <td class="small text-muted">{{ $quote->salesPerson?->name ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('retail-quotations.show', $quote) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                <form method="POST" action="{{ route('retail-quotations.open-pos', $quote) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-info">Open in POS</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No retail quotations yet. Save one from POS.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($quotations->hasPages())
            <div class="card-footer">{{ $quotations->links() }}</div>
        @endif
    </div>
</div>
@endsection
