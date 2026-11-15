@extends('sales_person.layout')

@section('title', $saleReturn->return_no)

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $saleReturn->return_no }}</h1>
            <p class="text-muted small mb-0">{{ $saleReturn->return_date->format('Y-m-d') }} · {{ $saleReturn->customer?->display_name }}</p>
        </div>
        <a href="{{ route('sales-person.sale-returns.index') }}" class="btn btn-sm btn-outline-secondary">All returns</a>
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light"><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">List</th><th class="text-end">Effective</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            @foreach($saleReturn->items as $item)
                                <tr>
                                    <td>{{ $item->product?->name }} @if($item->variation)<small class="text-muted">({{ $item->variation->variation_name }})</small>@endif</td>
                                    <td class="text-end">{{ number_format((float)$item->quantity, 2) }}</td>
                                    <td class="text-end text-muted">{{ number_format($item->listUnitPrice(), 2) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($item->effectiveUnitPrice(), 2) }}</td>
                                    <td class="text-end">{{ number_format((float)$item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr><th colspan="4" class="text-end">Return total</th><th class="text-end">{{ number_format((float)$saleReturn->total, 2) }}</th></tr>
                            <tr><th colspan="4" class="text-end">Claimable (30%)</th><th class="text-end text-success">{{ number_format((float)$saleReturn->claimable_amount, 2) }}</th></tr>
                            <tr><th colspan="4" class="text-end">Claimed</th><th class="text-end">{{ number_format((float)$saleReturn->claimed_amount, 2) }}</th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            @if($saleReturn->claims->isNotEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-header">Claimed on invoices</div>
                    <ul class="list-group list-group-flush">
                        @foreach($saleReturn->claims as $claim)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $claim->order?->invoice_number ?: $claim->order?->order_number }}</span>
                                <strong>Rs {{ number_format((float)$claim->amount, 2) }}</strong>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
