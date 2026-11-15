@extends('erp.layout')

@section('title', 'Purchase Payment Management')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-cash-stack me-2 text-primary"></i>Purchase Payment Management</h1>
            <p class="text-muted small mb-0">Each PO with payment methods used and <strong>full balance due</strong>. Filter by payment method or open <strong>All payments</strong> tab.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">
                Due purchases (filtered): <strong>{{ $duePurchaseCount }}</strong>
            </span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                Total outstanding: <strong>Rs {{ number_format($totalOutstanding, 2) }}</strong>
            </span>
        </div>
    </div>

    @include('erp.purchase-payment-management._subnav')

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('purchase-payment-management.index') }}" class="row g-2 align-items-end">
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
                    <label class="form-label form-label-sm mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All (excl. cancelled)</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Purchase type</label>
                    <select name="purchase_type" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="credit" {{ request('purchase_type') === 'credit' ? 'selected' : '' }}>Credit</option>
                        <option value="cash" {{ request('purchase_type') === 'cash' ? 'selected' : '' }}>Full Payment</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Payment method</label>
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="">Any</option>
                        <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="cheque" {{ request('payment_method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Paid / Due</label>
                    <select name="payment_state" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="due" {{ request('payment_state') === 'due' ? 'selected' : '' }}>Has balance due</option>
                        <option value="partial" {{ request('payment_state') === 'partial' ? 'selected' : '' }}>Partially paid</option>
                        <option value="paid" {{ request('payment_state') === 'paid' ? 'selected' : '' }}>Fully paid</option>
                        <option value="unpaid" {{ request('payment_state') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label form-label-sm mb-1">From</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label form-label-sm mb-1">To</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    <a href="{{ route('purchase-payment-management.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>PO No</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Payment methods</th>
                        <th class="text-end">Total (Rs)</th>
                        <th class="text-end">Returned</th>
                        <th class="text-end">Net (Rs)</th>
                        <th class="text-end">Paid (Rs)</th>
                        <th class="text-end">Due (Rs)</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        @php
                            $paid = \App\Services\PurchasePaymentManagementQuery::purchasePaidAmount($purchase);
                            $net = \App\Services\PurchasePaymentManagementQuery::purchaseNetTotal($purchase);
                            $returned = (float) ($purchase->returns_sum_total ?? 0);
                            $due = \App\Services\PurchasePaymentManagementQuery::purchaseDueAmount($purchase);
                            $methods = \App\Services\PurchasePaymentManagementQuery::paymentTotalsByMethod($purchase);
                            $filterMethod = request('payment_method') ? strtolower(trim((string) request('payment_method'))) : null;
                        @endphp
                        <tr class="{{ $due > 0.00001 ? 'table-warning' : '' }}">
                            <td class="fw-semibold">{{ $purchase->purchase_no }}</td>
                            <td class="text-muted small">{{ $purchase->date->format('Y-m-d') }}</td>
                            <td>{{ optional($purchase->supplier)->name ?? '—' }}</td>
                            <td class="small">{{ $purchase->purchase_type_label }}</td>
                            <td>
                                @if($purchase->status === 'draft')
                                    <span class="badge bg-secondary">Draft</span>
                                @else
                                    <span class="badge bg-success">Confirmed</span>
                                @endif
                            </td>
                            <td class="small">
                                @if(count($methods) === 0)
                                    <span class="text-muted">—</span>
                                @else
                                    @foreach($methods as $methodKey => $methodAmount)
                                        @if(!$filterMethod || $filterMethod === $methodKey)
                                        <span class="badge bg-light text-dark border me-1 mb-1">
                                            {{ \App\Services\PurchasePaymentManagementQuery::paymentMethodLabel($methodKey) }}
                                            <span class="font-monospace">Rs {{ number_format($methodAmount, 2) }}</span>
                                        </span>
                                        @endif
                                    @endforeach
                                @endif
                            </td>
                            <td class="text-end font-monospace">{{ number_format((float) $purchase->total, 2) }}</td>
                            <td class="text-end font-monospace {{ $returned > 0 ? 'text-warning' : 'text-muted' }}">{{ number_format($returned, 2) }}</td>
                            <td class="text-end font-monospace">{{ number_format($net, 2) }}</td>
                            <td class="text-end font-monospace text-success">{{ number_format($paid, 2) }}</td>
                            <td class="text-end font-monospace {{ $due > 0.00001 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ number_format($due, 2) }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-outline-primary">View</a>
                                @if($due > 0.00001)
                                    <a href="{{ route('purchases.show', $purchase) }}#addPaymentModal" class="btn btn-sm btn-success">Add payment</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-5">No purchases match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($purchases->hasPages())
            <div class="card-footer bg-white border-top-0">{{ $purchases->links() }}</div>
        @endif
    </div>
</div>
@endsection

