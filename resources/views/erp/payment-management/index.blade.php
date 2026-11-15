@extends('erp.layout')

@section('title', 'Payment Management')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-wallet2 me-2 text-primary"></i>Payment Management</h1>
            <p class="text-muted small mb-0">Order payments, due amounts, and quick links to record payments.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">
                Due orders (filtered): <strong>{{ $dueOrderCount }}</strong>
            </span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                Total outstanding: <strong>Rs {{ number_format($totalOutstanding, 2) }}</strong>
            </span>
        </div>
    </div>

    @include('erp.payment-management._subnav')

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('payment-management.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Order No</label>
                    <input type="text" name="order_no" class="form-control form-control-sm" value="{{ request('order_no') }}" placeholder="ORD-â€¦">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach(['pending' => 'Pending', 'processing' => 'Processing', 'afterchecking' => 'After Checking', 'completed' => 'Completed'] as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Sales Person</label>
                    <select name="sales_person_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($salesPersons as $sp)
                            <option value="{{ $sp->id }}" {{ (string) request('sales_person_id') === (string) $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1" for="pm_customer_id">Customer</label>
                    <select name="customer_id" id="pm_customer_id" class="form-select form-select-sm pm-customer-select2">
                        <option value="">All</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (string) request('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                                {{ $customer->display_name }}@if(filled($customer->phone)) ({{ $customer->phone }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label form-label-sm mb-1">Paid / Due</label>
                    <select name="payment_state" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="due" {{ request('payment_state') === 'due' ? 'selected' : '' }}>Due</option>
                        <option value="paid" {{ request('payment_state') === 'paid' ? 'selected' : '' }}>Paid</option>
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
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    <a href="{{ route('payment-management.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Sales person</th>
                        <th>Status</th>
                        <th class="text-end">Total (Rs)</th>
                        <th class="text-end">Paid (Rs)</th>
                        <th class="text-end">Due (Rs)</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $paid = (float) ($order->payments_sum_amount ?? 0);
                            $due = max(0, (float) $order->total_amount - $paid);
                        @endphp
                        <tr class="{{ $due > 0.00001 ? 'table-warning' : '' }}">
                            <td class="fw-semibold">{{ $order->order_number }}</td>
                            <td class="text-muted small">{{ $order->order_date->format('Y-m-d') }}</td>
                            <td>{{ optional($order->customer)->display_name ?? '—' }}</td>
                            <td class="text-muted small">
                                {{ ($order->salesPerson && $order->salesPerson->role === 'sales_person') ? $order->salesPerson->name : '—' }}
                            </td>
                            <td><span class="badge bg-secondary">{{ ucfirst($order->status) }}</span></td>
                            <td class="text-end font-monospace">{{ number_format((float) $order->total_amount, 2) }}</td>
                            <td class="text-end font-monospace text-success">{{ number_format($paid, 2) }}</td>
                            <td class="text-end font-monospace {{ $due > 0.00001 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ number_format($due, 2) }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                                @if($due > 0.00001 && $order->canAcceptPayments())
                                    <a href="{{ route('orders.show', $order) }}#add-payment" class="btn btn-sm btn-success">Add payment</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">No orders match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="card-footer bg-white border-top-0">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    jQuery(function ($) {
        if (!$.fn.select2) return;
        $('#pm_customer_id').select2({
            placeholder: 'All customers',
            allowClear: true,
            width: '100%',
            dropdownParent: $('body'),
        });
    });
</script>
@endsection
