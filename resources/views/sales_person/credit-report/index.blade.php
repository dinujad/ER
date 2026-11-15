@extends('sales_person.layout')

@section('title', 'Credit Report')

@section('content')
<style>
    .sales-hero { background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); color: #fff; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; }
    .sales-hero h1 { font-weight: 700; font-size: 1.35rem; margin: 0; }
    .sum-card { border: none; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); padding: 1.1rem 1.25rem; height: 100%; }
    .sum-card .sum-value { font-size: 1.25rem; font-weight: 700; }
    .sum-card .sum-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.04em; }
</style>

<div class="sales-hero">
    <h1><i class="bi bi-journal-text me-2"></i> Credit Report</h1>
    <p class="mb-0 mt-1 text-white-50">ඔයාගේ customers වලට දුන්න credit එක. Business name අනුව.</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('sales-person.credit-report.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label form-label-sm mb-1">Business / order</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{ $q }}" placeholder="Business name or order number">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
            </div>
            <div class="col-md-2">
                <input type="hidden" name="credit_only" value="0">
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" name="credit_only" value="1" id="credit_only" @checked($creditOnly)>
                    <label class="form-check-label small" for="credit_only">Credit තියෙන ඒවා විතරයි</label>
                </div>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="{{ route('sales-person.credit-report.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card sum-card">
            <div class="sum-label">Businesses</div>
            <div class="sum-value">{{ $summary->businesses }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card sum-card">
            <div class="sum-label">Orders</div>
            <div class="sum-value">{{ $summary->orders }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card sum-card">
            <div class="sum-label">Paid</div>
            <div class="sum-value text-success">Rs {{ number_format($summary->paid, 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card sum-card">
            <div class="sum-label">Credit due</div>
            <div class="sum-value text-danger">Rs {{ number_format($summary->credit, 2) }}</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Business summary</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Business name</th>
                    <th class="text-end">Orders</th>
                    <th class="text-end">Invoice</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Credit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byBusiness as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row->business }}</td>
                        <td class="text-end">{{ $row->orders }}</td>
                        <td class="text-end font-monospace">{{ number_format($row->net, 2) }}</td>
                        <td class="text-end font-monospace">{{ number_format($row->paid, 2) }}</td>
                        <td class="text-end font-monospace {{ $row->credit > 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format($row->credit, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">මේ filter එකට credit එකක් නැහැ.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Order details</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Order</th>
                    <th>Business name</th>
                    <th>Status</th>
                    <th class="text-end">Invoice</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Credit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $row)
                    <tr>
                        <td>{{ $row->order->order_date?->format('Y-m-d') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('sales-person.orders.show', $row->order) }}" class="fw-semibold text-decoration-none">{{ $row->order->order_number }}</a>
                            @if($row->order->invoice_number)
                                <div class="small text-muted">{{ $row->order->invoice_number }}</div>
                            @endif
                        </td>
                        <td>{{ $row->business }}</td>
                        <td class="small">{{ ucfirst($row->order->status) }}</td>
                        <td class="text-end font-monospace">{{ number_format($row->net, 2) }}</td>
                        <td class="text-end font-monospace">{{ number_format($row->paid, 2) }}</td>
                        <td class="text-end font-monospace {{ $row->credit > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ number_format($row->credit, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Orders නැහැ.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
        <div class="card-footer bg-white">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
