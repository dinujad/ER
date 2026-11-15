@extends('erp.layout')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-hourglass-split me-2 text-warning"></i>{{ $title }}</h1>
            <p class="text-muted small mb-0">
                Wholesale cheques that are <strong>received</strong> but <strong>not yet deposited</strong> to our bank.
                Deposit / Clear කිරීම Payment Management → Cheque Payments එකෙන්.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary">← Reports</a>
            <a href="{{ route('payment-management.cheques', ['cheque_status' => 'received']) }}" class="btn btn-sm btn-outline-primary">Manage cheques</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small text-uppercase">Pending cheques</div>
                    <div class="fs-4 fw-bold text-warning">{{ $data['chequeCount'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-warning">
                <div class="card-body text-center py-3">
                    <div class="text-muted small text-uppercase">Pending cheque amount</div>
                    <div class="fs-5 fw-bold text-warning">Rs {{ number_format($data['totalAmount'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small text-uppercase">Customers</div>
                    <div class="fs-4 fw-bold">{{ $data['customerCount'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small text-uppercase">Avg / cheque</div>
                    <div class="fs-5 fw-bold">
                        Rs {{ $data['chequeCount'] > 0 ? number_format($data['totalAmount'] / $data['chequeCount'], 2) : '0.00' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.show', 'pending-cheques') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Customer (business)</label>
                    <select name="customer_id" class="form-select form-select-sm">
                        <option value="">All wholesale customers</option>
                        @foreach($data['customers'] as $c)
                            <option value="{{ $c->id }}" @selected((string) request('customer_id') === (string) $c->id)>
                                {{ $c->display_name }}@if($c->phone) ({{ $c->phone }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Sales person</label>
                    <select name="sales_person_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($data['salesPersons'] as $sp)
                            <option value="{{ $sp->id }}" @selected((string) request('sales_person_id') === (string) $sp->id)>{{ $sp->name }}</option>
                        @endforeach
                    </select>
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
                    <label class="form-label form-label-sm mb-1">Pending ≥ days</label>
                    <input type="number" min="0" name="overdue_days" class="form-control form-control-sm" value="{{ request('overdue_days') }}" placeholder="e.g. 7">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                </div>
                <div class="col-12">
                    <a href="{{ route('reports.show', 'pending-cheques') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if($data['byCustomer']->isNotEmpty())
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white">
            <strong>By customer (business name)</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Business / Customer</th>
                            <th class="text-end">Cheques</th>
                            <th class="text-end">Pending amount (Rs)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['byCustomer'] as $group)
                        <tr>
                            <td class="fw-semibold">{{ $group->business_name }}</td>
                            <td class="text-end">{{ $group->cheque_count }}</td>
                            <td class="text-end font-monospace text-warning fw-semibold">{{ number_format($group->amount, 2) }}</td>
                            <td class="text-end">
                                @if($group->customer)
                                    <a href="{{ route('erp.customers.show', $group->customer) }}" class="btn btn-sm btn-outline-secondary">Profile</a>
                                    <a href="{{ route('reports.show', ['report' => 'pending-cheques', 'customer_id' => $group->customer->id]) }}" class="btn btn-sm btn-outline-warning">Filter</a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Pending cheque detail</strong>
            <span class="badge bg-light text-dark border">{{ $data['chequeCount'] }} row(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Business name</th>
                            <th>Order #</th>
                            <th>Sales person</th>
                            <th>Cheque date</th>
                            <th>Received</th>
                            <th>Days pending</th>
                            <th>Bank / Ref</th>
                            <th class="text-end">Amount (Rs)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['rows'] as $row)
                        <tr class="{{ $row->is_overdue ? 'table-warning' : '' }}">
                            <td>
                                <div class="fw-semibold">{{ $row->business_name }}</div>
                                @if($row->customer && $row->contact_name && strcasecmp($row->business_name, $row->contact_name) !== 0)
                                    <small class="text-muted">{{ $row->contact_name }}</small>
                                @endif
                            </td>
                            <td>
                                @if($row->order)
                                    <a href="{{ route('orders.show', $row->order) }}" class="text-decoration-none fw-semibold">{{ $row->order->order_number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small">{{ $row->sales_person }}</td>
                            <td>{{ $row->payment->cheque_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="small">{{ $row->payment->payment_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                @if($row->days_pending !== null)
                                    <span class="badge {{ $row->days_pending > 7 ? 'bg-danger' : ($row->days_pending > 0 ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                        {{ $row->days_pending }} day{{ $row->days_pending === 1 ? '' : 's' }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small">
                                {{ $row->payment->cheque_number ?: ($row->payment->reference_number ?? '—') }}
                                @if($row->payment->cheque_bank)
                                    <div class="text-muted">{{ $row->payment->cheque_bank }}</div>
                                @endif
                            </td>
                            <td class="text-end font-monospace fw-semibold text-warning">{{ number_format((float) $row->payment->amount, 2) }}</td>
                            <td class="text-end text-nowrap">
                                @if($row->order)
                                    <a href="{{ route('orders.show', $row->order) }}" class="btn btn-sm btn-outline-primary">Order</a>
                                @endif
                                <a href="{{ route('payment-management.cheques', ['cheque_status' => 'received', 'customer_id' => $row->customer?->id]) }}" class="btn btn-sm btn-outline-success">Deposit</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                No pending (undeposited) cheques match these filters.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($data['chequeCount'] > 0)
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="7" class="text-end">Total pending cheque amount</th>
                            <th class="text-end font-monospace text-warning">{{ number_format($data['totalAmount'], 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
