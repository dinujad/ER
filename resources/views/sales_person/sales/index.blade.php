@extends('sales_person.layout')

@section('title', 'Sales & Commission')

@section('content')
<style>
    .sales-hero { background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); color: #fff; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 10px 40px rgba(30,58,95,0.2); }
    .sales-hero h1 { font-weight: 700; font-size: 1.35rem; margin: 0; }
    .sum-card { border: none; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); padding: 1.1rem 1.25rem; height: 100%; }
    .sum-card .sum-value { font-size: 1.35rem; font-weight: 700; }
    .sum-card .sum-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.04em; }
    .section-card { border: none; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); overflow: hidden; }
    .section-card .card-header { background: #fff; border-bottom: 1px solid #eee; padding: 0.875rem 1.25rem; font-weight: 600; }
</style>

<div class="sales-hero d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h1><i class="bi bi-graph-up-arrow me-2"></i> Sales & Commission</h1>
        <p class="text-opacity-90 mb-0 mt-1">{{ $startDate->format('F Y') }} — sales, collections, and commission</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('sales-person.sales.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label form-label-sm mb-1">Month</label>
                <select name="month" class="form-select form-select-sm">
                    @foreach($monthOptions as $opt)
                        <option value="{{ $opt['value'] }}" {{ $month === $opt['value'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">View</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card sum-card">
            <div class="sum-label">Total sales</div>
            <div class="sum-value text-primary">Rs {{ number_format($totalSales, 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card sum-card">
            <div class="sum-label">Orders</div>
            <div class="sum-value">{{ $orderCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card sum-card">
            <div class="sum-label">Collected</div>
            <div class="sum-value text-success">Rs {{ number_format($totalPaid, 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card sum-card">
            <div class="sum-label">Outstanding</div>
            <div class="sum-value {{ $totalDue > 0 ? 'text-danger' : 'text-muted' }}">Rs {{ number_format($totalDue, 2) }}</div>
        </div>
    </div>
</div>

<div id="commission" class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-4">
        <div class="card sum-card border-warning">
            <div class="sum-label">This month commission</div>
            <div class="sum-value text-warning">
                @if($monthCommission)
                    Rs {{ number_format($monthCommission->total_amount, 2) }}
                @else
                    —
                @endif
            </div>
            <small class="text-muted">
                @if($monthCommission)
                    @if($monthCommission->status === 'paid')
                        Paid
                    @else
                        Pending admin payment
                    @endif
                @else
                    Not set for this month yet
                @endif
            </small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="card sum-card border-primary">
            <div class="sum-label">Pending commission (all)</div>
            <div class="sum-value text-primary">Rs {{ number_format($commissionSummary['pendingTotal'], 2) }}</div>
            <small class="text-muted">Admin will pay this amount</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="card sum-card border-success">
            <div class="sum-label">Total earned</div>
            <div class="sum-value text-success">Rs {{ number_format($commissionSummary['totalEarned'], 2) }}</div>
            <small class="text-muted">All time paid commission</small>
        </div>
    </div>
</div>

@if($monthCommission)
<div class="card section-card mb-4">
    <div class="card-header">
        <span><i class="bi bi-wallet2 me-1"></i> Commission breakdown — {{ $monthCommission->month_label }}</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="small text-muted text-uppercase mb-1">Cash / bank collections</div>
                <div class="fw-semibold">Rs {{ number_format($monthCommission->cash_collection, 2) }}</div>
                <div class="small text-muted">Rate {{ number_format($monthCommission->cash_rate, 2) }}% → Rs {{ number_format($monthCommission->cash_commission, 2) }}</div>
            </div>
            <div class="col-md-6">
                <div class="small text-muted text-uppercase mb-1">Cheque collections</div>
                <div class="fw-semibold">Rs {{ number_format($monthCommission->cheque_collection, 2) }}</div>
                <div class="small text-muted">Rate {{ number_format($monthCommission->cheque_rate, 2) }}% → Rs {{ number_format($monthCommission->cheque_commission, 2) }}</div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="card section-card mb-4">
    <div class="card-header">
        <span><i class="bi bi-clock-history me-1"></i> Commission history</span>
    </div>
    <div class="card-body p-0">
        @if(empty($commissionSummary['history']))
            <div class="p-4 text-center text-muted">No commission records yet.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Month</th>
                            <th class="text-end">Amount (Rs)</th>
                            <th>Status</th>
                            <th>Paid date</th>
                            <th>Payment method</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($commissionSummary['history'] as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->month_label }}</td>
                            <td class="text-end font-monospace">{{ number_format($row->total_amount, 2) }}</td>
                            <td>
                                @if($row->status === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                @if($row->paid_at)
                                    {{ \Carbon\Carbon::parse($row->paid_at)->format('d M Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small text-capitalize">
                                @if($row->payment_method)
                                    {{ str_replace('_', ' ', $row->payment_method) }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card section-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-receipt me-1"></i> Sales by order</span>
        <a href="{{ route('sales-person.orders.create') }}" class="btn btn-sm btn-primary">New order</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th class="text-end">Total (Rs)</th>
                        <th class="text-end">Paid (Rs)</th>
                        <th class="text-end">Due (Rs)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $paid = (float) ($order->payments_sum_amount ?? $order->payments->sum('amount'));
                            $due = max(0, $order->invoice_total - $paid);
                        @endphp
                        <tr class="{{ $due > 0 ? 'table-warning' : '' }}">
                            <td class="fw-semibold">{{ $order->order_number }}</td>
                            <td class="small text-muted">{{ $order->order_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                @if($order->customer)
                                    @if($order->customer->customer_type === 'wholesale' && (int) $order->customer->added_by === (int) auth()->id())
                                        <a href="{{ route('sales-person.customers.show', $order->customer_id) }}" class="text-decoration-none">{{ $order->customer->display_name }}</a>
                                    @else
                                        {{ $order->customer->display_name }}
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="badge bg-secondary">{{ ucfirst($order->status) }}</span></td>
                            <td class="text-end font-monospace">{{ number_format($order->invoice_total, 2) }}</td>
                            <td class="text-end font-monospace text-success">{{ number_format($paid, 2) }}</td>
                            <td class="text-end font-monospace {{ $due > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ number_format($due, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('sales-person.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No sales for this month.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($orders->isNotEmpty())
                <tfoot class="table-light fw-semibold">
                    <tr>
                        <td colspan="4" class="text-end">Period total</td>
                        <td class="text-end font-monospace">{{ number_format($totalSales, 2) }}</td>
                        <td class="text-end font-monospace text-success">{{ number_format($totalPaid, 2) }}</td>
                        <td class="text-end font-monospace text-danger">{{ number_format($totalDue, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
