@extends('erp.layout')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h4 class="mb-0">Purchases</h4>
    <a href="{{ route('purchases.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Purchase</a>
</div>

<form method="GET" action="{{ route('purchases.index') }}" class="card mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-end flex-wrap">
            <div class="col-md-2">
                <label class="form-label small mb-0">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="all">All</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Payment</label>
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="all">All</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="PO No, Ref, Supplier">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-outline-primary btn-sm flex-grow-1">Filter</button>
                @if(request()->hasAny(['date_from', 'date_to', 'supplier_id', 'status', 'payment_status', 'search']))
                <a href="{{ route('purchases.index', array_merge(request()->only(['date_from', 'date_to', 'supplier_id', 'status', 'payment_status', 'search']), ['export' => 'csv'])) }}"
                   class="btn btn-success btn-sm" title="Export filtered list">
                    <i class="bi bi-download"></i>
                </a>
                @endif
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Purchase No</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $p)
                    <tr>
                        <td><strong>{{ $p->purchase_no }}</strong></td>
                        <td>{{ $p->date->format('d M Y') }}</td>
                        <td>{{ $p->supplier->name ?? '—' }}</td>
                        <td>{{ number_format($p->total, 2) }}</td>
                        <td>{{ number_format($p->total_paid, 2) }}</td>
                        <td class="{{ $p->balance > 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format($p->balance, 2) }}</td>
                        <td>
                            @if($p->status !== \App\Models\Purchase::STATUS_CANCELLED && $p->status !== \App\Models\Purchase::STATUS_DRAFT)
                                @if($p->payment_status === 'paid')<span class="badge bg-success">Paid</span>
                                @elseif($p->payment_status === 'partial')<span class="badge bg-warning text-dark">Partial</span>
                                @else<span class="badge bg-secondary">Unpaid</span>
                                @endif
                                @if($p->balance > 0)<a href="{{ route('purchases.show', $p) }}#addPaymentModal" class="btn btn-sm btn-outline-success ms-1" title="Add payment">+ Pay</a>@endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($p->status === 'draft')<span class="badge bg-secondary">Draft</span>
                            @elseif($p->status === 'confirmed')<span class="badge bg-success">Confirmed</span>
                            @else<span class="badge bg-danger">Cancelled</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('purchases.show', $p) }}" class="btn btn-sm btn-outline-info">View</a>
                            @if($p->status === \App\Models\Purchase::STATUS_CONFIRMED)
                                @php
                                    $canReturn = $p->items->contains(fn ($i) => (float) $i->quantity - (float) $i->quantity_returned > 0.0001);
                                @endphp
                                @if($canReturn)
                                <a href="{{ route('purchases.show', $p) }}#returnToSupplierModal" class="btn btn-sm btn-outline-warning" title="Return items to supplier">Return</a>
                                @endif
                            @endif
                            @if($p->status !== 'cancelled')
                            <a href="{{ route('purchases.report', $p) }}" class="btn btn-sm btn-outline-dark" target="_blank" title="Print report"><i class="bi bi-printer"></i></a>
                            @endif
                            @if($p->status === 'draft')
                            @canRoute('purchases.edit')
<a href="{{ route('purchases.edit', $p) }}" class="btn btn-sm btn-outline-primary">Edit</a>
@endcanRoute
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No purchases found. <a href="{{ route('purchases.create') }}">Add purchase</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0">{{ $purchases->links() }}</div>
    </div>
</div>
@endsection
