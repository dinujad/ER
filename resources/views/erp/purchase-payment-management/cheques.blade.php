@extends('erp.layout')

@section('title', 'Purchase cheque payments')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-cash-stack me-2 text-primary"></i>Purchase Payment Management</h1>
            <p class="text-muted small mb-0">
                Each <strong>cheque</strong> on a purchase (initial or additional payment). Filter by <strong>cheque date</strong>, payment date, and see purchase <strong>balance due</strong>.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-secondary-subtle text-secondary border px-3 py-2">
                Cheques (filtered): <strong>{{ $chequeCount }}</strong>
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                Cheque amount: <strong>Rs {{ number_format($chequeAmountSum, 2) }}</strong>
            </span>
        </div>
    </div>

    @include('erp.purchase-payment-management._subnav')

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('purchase-payment-management.cheques') }}" class="row g-2 align-items-end">
                <div class="col-12">
                    <div class="fw-semibold small text-muted mb-1">Cheque date (realization)</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Cheque date from</label>
                    <input type="date" name="cheque_date_from" class="form-control form-control-sm" value="{{ request('cheque_date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Cheque date to</label>
                    <input type="date" name="cheque_date_to" class="form-control form-control-sm" value="{{ request('cheque_date_to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Cheque status</label>
                    <select name="cheque_state" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="overdue" {{ request('cheque_state') === 'overdue' ? 'selected' : '' }}>Overdue (past date)</option>
                        <option value="today" {{ request('cheque_state') === 'today' ? 'selected' : '' }}>Due today</option>
                        <option value="upcoming" {{ request('cheque_state') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    </select>
                </div>
                <div class="col-12 mt-2">
                    <div class="fw-semibold small text-muted mb-1">Payment date (recorded)</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Payment date from</label>
                    <input type="date" name="payment_date_from" class="form-control form-control-sm" value="{{ request('payment_date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Payment date to</label>
                    <input type="date" name="payment_date_to" class="form-control form-control-sm" value="{{ request('payment_date_to') }}">
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
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    <a href="{{ route('purchase-payment-management.cheques') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Cheque date</th>
                        <th>Payment date</th>
                        <th>PO No</th>
                        <th>Supplier</th>
                        <th>Cheque # / Bank</th>
                        <th class="text-end">Amount (Rs)</th>
                        <th class="text-end">Purchase due (Rs)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cheques as $row)
                        @php
                            $purchase = $purchasesById[$row->purchase_id] ?? null;
                            $supplier = $suppliersById[$row->supplier_id] ?? optional($purchase)->supplier;
                            $purchaseDue = $purchase ? \App\Services\PurchasePaymentManagementQuery::purchaseDueAmount($purchase) : null;
                            $chequeDate = $row->cheque_date ? \Illuminate\Support\Carbon::parse($row->cheque_date) : null;
                            $isOverdue = $chequeDate && $chequeDate->isPast() && !$chequeDate->isToday();
                        @endphp
                        <tr class="{{ $isOverdue ? 'table-danger' : '' }}">
                            <td class="fw-semibold">
                                @if($chequeDate)
                                    {{ $chequeDate->format('Y-m-d') }}
                                    @if($isOverdue)
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    @elseif($chequeDate->isToday())
                                        <span class="badge bg-warning text-dark ms-1">Today</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $row->payment_date ? \Illuminate\Support\Carbon::parse($row->payment_date)->format('Y-m-d') : '—' }}
                            </td>
                            <td>
                                <a href="{{ route('purchases.show', $row->purchase_id) }}" class="fw-semibold text-decoration-none">{{ $row->purchase_no }}</a>
                                @if($row->source === 'initial')
                                    <span class="badge bg-light text-muted border ms-1">Initial</span>
                                @endif
                            </td>
                            <td>{{ $supplier->name ?? '—' }}</td>
                            <td class="small">
                                @if($row->cheque_number)#{{ $row->cheque_number }}@endif
                                @if($row->cheque_bank) · {{ $row->cheque_bank }}@endif
                                @if(!$row->cheque_number && !$row->cheque_bank)—@endif
                            </td>
                            <td class="text-end font-monospace">{{ number_format((float) $row->amount, 2) }}</td>
                            <td class="text-end font-monospace {{ $purchaseDue && $purchaseDue > 0.00001 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ $purchaseDue !== null ? number_format($purchaseDue, 2) : '—' }}
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('purchases.show', $row->purchase_id) }}" class="btn btn-sm btn-outline-primary">Purchase</a>
                                @if($purchaseDue && $purchaseDue > 0.00001)
                                    <a href="{{ route('purchases.show', $row->purchase_id) }}#addPaymentModal" class="btn btn-sm btn-success">Pay</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No cheque payments match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($cheques->hasPages())
            <div class="card-footer bg-white border-top-0">{{ $cheques->links() }}</div>
        @endif
    </div>
</div>
@endsection

