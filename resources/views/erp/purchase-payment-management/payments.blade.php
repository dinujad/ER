@extends('erp.layout')

@section('title', 'Purchase payments by method')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-cash-stack me-2 text-primary"></i>Purchase Payment Management</h1>
            <p class="text-muted small mb-0">Each payment line by PO number and method. Purchase <strong>full due</strong> shown on every row.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-secondary-subtle text-secondary border px-3 py-2">
                Payments (filtered): <strong>{{ $paymentCount }}</strong>
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                Amount: <strong>Rs {{ number_format($paymentAmountSum, 2) }}</strong>
            </span>
        </div>
    </div>

    @include('erp.purchase-payment-management._subnav')

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('purchase-payment-management.payments') }}" class="row g-2 align-items-end">
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
                    <label class="form-label form-label-sm mb-1">Payment method</label>
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="">All methods</option>
                        <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="cheque" {{ request('payment_method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Purchase due</label>
                    <select name="payment_state" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="due" {{ request('payment_state') === 'due' ? 'selected' : '' }}>PO still has balance due</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Payment date from</label>
                    <input type="date" name="payment_date_from" class="form-control form-control-sm" value="{{ request('payment_date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Payment date to</label>
                    <input type="date" name="payment_date_to" class="form-control form-control-sm" value="{{ request('payment_date_to') }}">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    <a href="{{ route('purchase-payment-management.payments') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
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
                        <th>Payment date</th>
                        <th>Supplier</th>
                        <th>Method</th>
                        <th>Details</th>
                        <th class="text-end">This payment (Rs)</th>
                        <th class="text-end">PO total (Rs)</th>
                        <th class="text-end">PO due (Rs)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentRows as $row)
                        @php
                            $purchase = $purchasesById[$row->purchase_id] ?? null;
                            $supplier = $suppliersById[$row->supplier_id] ?? optional($purchase)->supplier;
                            $purchaseDue = $purchase ? \App\Services\PurchasePaymentManagementQuery::purchaseDueAmount($purchase) : null;
                            $method = strtolower(trim((string) ($row->payment_method ?? '')));
                        @endphp
                        <tr class="{{ $purchaseDue && $purchaseDue > 0.00001 ? 'table-warning' : '' }}">
                            <td class="fw-semibold">
                                <a href="{{ route('purchases.show', $row->purchase_id) }}" class="text-decoration-none">{{ $row->purchase_no }}</a>
                            </td>
                            <td class="text-muted small">
                                {{ $row->payment_date ? \Illuminate\Support\Carbon::parse($row->payment_date)->format('Y-m-d') : '—' }}
                            </td>
                            <td>{{ $supplier->name ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $method === 'cheque' ? 'bg-info' : ($method === 'bank_transfer' ? 'bg-primary' : 'bg-success') }}">
                                    {{ \App\Services\PurchasePaymentManagementQuery::paymentMethodLabel($method) }}
                                </span>
                                @if($row->source === 'initial')
                                    <span class="badge bg-light text-muted border">Initial</span>
                                @endif
                            </td>
                            <td class="small">
                                @if($method === 'cheque')
                                    @if($row->cheque_number)#{{ $row->cheque_number }}@endif
                                    @if($row->cheque_bank) · {{ $row->cheque_bank }}@endif
                                    @if($row->cheque_date) · {{ \Illuminate\Support\Carbon::parse($row->cheque_date)->format('d M Y') }}@endif
                                @elseif($row->reference)
                                    {{ $row->reference }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end font-monospace">{{ number_format((float) $row->amount, 2) }}</td>
                            <td class="text-end font-monospace text-muted">{{ number_format((float) ($row->purchase_total ?? $purchase?->total ?? 0), 2) }}</td>
                            <td class="text-end font-monospace {{ $purchaseDue && $purchaseDue > 0.00001 ? 'text-danger fw-bold' : 'text-muted' }}">
                                {{ $purchaseDue !== null ? number_format($purchaseDue, 2) : '—' }}
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('purchases.show', $row->purchase_id) }}" class="btn btn-sm btn-outline-primary">PO</a>
                                @if($purchaseDue && $purchaseDue > 0.00001)
                                    <a href="{{ route('purchases.show', $row->purchase_id) }}#addPaymentModal" class="btn btn-sm btn-success">Pay</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">No payments match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($paymentRows->hasPages())
            <div class="card-footer bg-white border-top-0">{{ $paymentRows->links() }}</div>
        @endif
    </div>
</div>
@endsection
