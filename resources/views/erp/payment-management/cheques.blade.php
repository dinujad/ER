@extends('erp.layout')

@section('title', 'Cheque Payments')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-wallet2 me-2 text-primary"></i>Payment Management</h1>
            <p class="text-muted small mb-0">
                Each <strong>cheque</strong> you recorded on an order appears as one row.
                <strong>Received</strong> â†’ PDC account (customer due reduced).
                <strong>Deposited</strong> â†’ sent to bank.
                <strong>Cleared</strong> â†’ funds in bank (PDC â†’ Bank).
                <strong>Returned</strong> â†’ invoice unpaid again; customer & sales person notified.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-secondary-subtle text-secondary border px-3 py-2">
                Cheques (filtered): <strong>{{ $chequeCount }}</strong>
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                Total amount: <strong>Rs {{ number_format($chequeAmountSum, 2) }}</strong>
            </span>
        </div>
    </div>

    @include('erp.payment-management._subnav')

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('payment-management.cheques') }}" class="row g-2 align-items-end">
                <div class="col-12">
                    <div class="fw-semibold small text-muted mb-1">Cheque date</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Cheque date from</label>
                    <input type="date" name="cheque_date_from" class="form-control form-control-sm" value="{{ request('cheque_date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Cheque date to</label>
                    <input type="date" name="cheque_date_to" class="form-control form-control-sm" value="{{ request('cheque_date_to') }}">
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
                    <label class="form-label form-label-sm mb-1">Order No</label>
                    <input type="text" name="order_no" class="form-control form-control-sm" value="{{ request('order_no') }}" placeholder="ORD-â€¦">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1" for="pm_chq_customer_id">Customer</label>
                    <select name="customer_id" id="pm_chq_customer_id" class="form-select form-select-sm pm-chq-customer-select2">
                        <option value="">All</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (string) request('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                                {{ $customer->display_name }}@if(filled($customer->phone)) ({{ $customer->phone }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Status</label>
                    <select name="cheque_status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        <option value="received" @selected(request('cheque_status') === 'received')>Received (PDC)</option>
                        <option value="deposited" @selected(request('cheque_status') === 'deposited')>Deposited</option>
                        <option value="cleared" @selected(request('cheque_status') === 'cleared')>Cleared</option>
                        <option value="bounced" @selected(request('cheque_status') === 'bounced')>Returned / Bounced</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    <a href="{{ route('payment-management.cheques') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
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
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Amount (Rs)</th>
                        <th>Reference</th>
                        <th>Status / Actions</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        @php $order = $payment->order; @endphp
                        <tr>
                            <td class="fw-semibold">
                                {{ $payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : '—' }}
                            </td>
                            <td class="text-muted small">{{ $payment->payment_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                @if($order)
                                    <a href="{{ route('orders.show', $order) }}#add-payment" class="fw-semibold text-decoration-none">{{ $order->order_number }}</a>
                                    @if($payment->chequeBatch)
                                        <div class="small text-muted">{{ $payment->chequeBatch->batch_number }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ optional(optional($order)->customer)->display_name ?? '—' }}</td>
                            <td class="font-monospace">{{ number_format((float) $payment->amount, 2) }}</td>
                            <td class="small">{{ $payment->reference_number ?? '—' }}</td>
                            <td>@include('erp.payment-management._cheque-status', ['payment' => $payment])</td>
                            <td class="text-end text-nowrap">
                                @if($order)
                                    <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Order</a>
                                @endif
                                @if(strtolower((string) $payment->payment_method) === 'cheque' && $payment->cheque_image_path)
                                    <a href="{{ route('order-payments.cheque-image', $payment) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Photo</a>
                                @endif
                                @if($payment->cheque_exception_requested)
                                    <span class="badge bg-warning text-dark">Exception</span>
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
        @if($payments->hasPages())
            <div class="card-footer bg-white border-top-0">{{ $payments->links() }}</div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    jQuery(function ($) {
        if (!$.fn.select2) return;
        $('#pm_chq_customer_id').select2({
            placeholder: 'All customers',
            allowClear: true,
            width: '100%',
            dropdownParent: $('body'),
        });
    });
</script>
@endsection
