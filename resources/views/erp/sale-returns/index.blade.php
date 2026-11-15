@extends('erp.layout')

@section('title', 'Sale Returns')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-arrow-counterclockwise me-2 text-warning"></i>Sale Returns</h1>
            <p class="text-muted small mb-0">Retail &amp; wholesale returns — stock restocked; refunds reduce sales / profit collections.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-warning-subtle text-dark border px-3 py-2">
                Total returned (filtered): <strong>Rs {{ number_format($totalReturned, 2) }}</strong>
            </span>
            <a href="{{ route('sale-returns.create') }}" class="btn btn-warning btn-sm">
                <i class="bi bi-plus-lg me-1"></i> New Sale Return
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
            <form method="GET" action="{{ route('sale-returns.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Return No</label>
                    <input type="text" name="return_no" class="form-control form-control-sm" value="{{ request('return_no') }}" placeholder="SR-…">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Order / Invoice</label>
                    <input type="text" name="order_no" class="form-control form-control-sm" value="{{ request('order_no') }}" placeholder="ORD / 26JUL-DERM-1001">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Customer</label>
                    <select name="customer_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ (string) request('customer_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label form-label-sm mb-1">Channel</label>
                    <select name="channel" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="wholesale" {{ request('channel') === 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                        <option value="retail" {{ request('channel') === 'retail' ? 'selected' : '' }}>Retail</option>
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
                <div class="col-md-1 d-flex gap-1">
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
                        <th>Return No</th>
                        <th>Date</th>
                        <th>Order / Invoice</th>
                        <th>Customer</th>
                        <th>Channel</th>
                        <th class="text-end">Amount (Rs)</th>
                        <th class="text-end">Claimable</th>
                        <th class="text-end">Claimed</th>
                        <th>Reason</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $ret)
                        <tr>
                            <td class="fw-semibold">{{ $ret->return_no }}</td>
                            <td class="text-muted small">{{ $ret->return_date->format('Y-m-d') }}</td>
                            <td>
                                @if($ret->order)
                                    <a href="{{ route('orders.show', $ret->order) }}">{{ $ret->order->invoice_number ?: $ret->order->order_number }}</a>
                                @else — @endif
                            </td>
                            <td>{{ $ret->customer?->display_name ?? '—' }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($ret->channel) }}</span></td>
                            <td class="text-end font-monospace">{{ number_format((float) $ret->total, 2) }}</td>
                            <td class="text-end text-success">{{ number_format((float) $ret->claimable_amount, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $ret->claimed_amount, 2) }}</td>
                            <td class="small text-muted">{{ \Illuminate\Support\Str::limit($ret->reason, 40) ?: '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('sale-returns.show', $ret) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">No sale returns yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($returns->hasPages())
            <div class="card-footer">{{ $returns->links() }}</div>
        @endif
    </div>
</div>
@endsection
