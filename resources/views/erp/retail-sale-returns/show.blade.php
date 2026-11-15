@extends('erp.layout')

@section('title', 'Retail Return ' . $saleReturn->return_no)

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $saleReturn->return_no }}</h1>
            <p class="text-muted small mb-0">
                {{ $saleReturn->return_date->format('Y-m-d') }}
                · Retail / POS
                · {{ $saleReturn->customer?->display_name ?? 'Walk-in' }}
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($saleReturn->order)
                <a href="{{ route('pos.receipt', $saleReturn->order) }}" class="btn btn-outline-secondary btn-sm">POS receipt</a>
            @endif
            <a href="{{ route('retail-sale-returns.index') }}" class="btn btn-outline-secondary btn-sm">All retail returns</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header"><strong>Returned items — restocked to retail</strong></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit</th>
                                <th class="text-end">Line</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($saleReturn->items as $item)
                                <tr>
                                    <td>
                                        {{ $item->product?->name ?? '—' }}
                                        @if($item->variation)
                                            <small class="d-block text-muted">({{ $item->variation->variation_name }})</small>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format((float) $item->quantity, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total</th>
                                <th class="text-end">{{ number_format((float) $saleReturn->total, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <div class="mb-2"><span class="text-muted">Reason</span><div>{{ $saleReturn->reason ?: '—' }}</div></div>
                    <div class="mb-2"><span class="text-muted">Created by</span><div>{{ $saleReturn->creator?->name ?? '—' }}</div></div>
                    <div><span class="text-muted">POS sale</span>
                        <div>
                            @if($saleReturn->order)
                                {{ $saleReturn->order->invoice_number ?: $saleReturn->order->order_number }}
                            @else — @endif
                        </div>
                    </div>
                </div>
            </div>
            @if($saleReturn->refunds->isNotEmpty())
                <div class="card shadow-sm border-0">
                    <div class="card-header"><strong>Refund payments</strong></div>
                    <ul class="list-group list-group-flush">
                        @foreach($saleReturn->refunds as $pay)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ ucfirst($pay->payment_method) }} · {{ optional($pay->payment_date)->format('Y-m-d') }}</span>
                                <strong class="text-danger">−{{ number_format((float) $pay->amount, 2) }}</strong>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="card shadow-sm border-0">
                    <div class="card-body text-muted small">No cash refund — unpaid / credit balance was reduced instead.</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
