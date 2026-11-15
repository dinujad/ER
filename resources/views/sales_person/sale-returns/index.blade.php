@extends('sales_person.layout')

@section('title', 'Sale Returns')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1">Sale Returns</h1>
            <p class="text-muted small mb-0">Return items restock automatically. 30% of return value becomes claim credit for other invoices.</p>
        </div>
        <a href="{{ route('sales-person.sale-returns.create') }}" class="btn btn-warning btn-sm">New Sale Return</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="row g-2 mb-3">
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body py-2 text-center">Returned<br><strong>Rs {{ number_format($totalReturned, 2) }}</strong></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body py-2 text-center">Claimable (30%)<br><strong class="text-success">Rs {{ number_format($totalClaimable - $totalClaimed, 2) }}</strong></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body py-2 text-center">Already claimed<br><strong>Rs {{ number_format($totalClaimed, 2) }}</strong></div></div></div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Return</th>
                        <th>Date</th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th class="text-end">Returned</th>
                        <th class="text-end">Claimable</th>
                        <th class="text-end">Claimed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $ret)
                        <tr>
                            <td class="fw-semibold">{{ $ret->return_no }}</td>
                            <td>{{ $ret->return_date->format('Y-m-d') }}</td>
                            <td>{{ $ret->order?->invoice_number ?: $ret->order?->order_number }}</td>
                            <td>{{ $ret->customer?->display_name ?? '—' }}</td>
                            <td class="text-end">{{ number_format((float) $ret->total, 2) }}</td>
                            <td class="text-end text-success">{{ number_format((float) $ret->claimable_amount, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $ret->claimed_amount, 2) }}</td>
                            <td><a href="{{ route('sales-person.sale-returns.show', $ret) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No returns yet.</td></tr>
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
