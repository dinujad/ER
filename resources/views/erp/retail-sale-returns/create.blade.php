@extends('erp.layout')

@section('title', 'New Retail Sales Return')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">New Retail Sales Return</h1>
            <p class="text-muted small mb-0">Find a POS sale, return items to retail stock, and refund the customer.</p>
        </div>
        <a href="{{ route('retail-sale-returns.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('retail-sale-returns.create') }}" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">POS receipt / Invoice No</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="e.g. ORD106 or 26JUL-DERM-1001" {{ $order ? '' : 'required' }}>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Find POS sale</button>
                </div>
            </form>
        </div>
    </div>

    @if(!empty($wrongChannel))
        <div class="alert alert-warning">That sale is wholesale. Use <a href="{{ route('sale-returns.create') }}">Sale Returns</a> for salesperson invoices.</div>
    @elseif(request()->filled('q') && !$order)
        <div class="alert alert-warning">No matching retail / POS sale found for “{{ request('q') }}”.</div>
    @endif

    @if($order)
        @if(!$order->canAcceptSaleReturn())
            <div class="alert alert-danger">
                Sale {{ $order->invoice_number ?: $order->order_number }} status is <strong>{{ $order->status }}</strong> — only completed / paid / credit POS sales can be returned.
            </div>
        @elseif($order->items->every(fn ($i) => $i->returnableQuantity() <= 0))
            <div class="alert alert-info">All items on this POS sale are already fully returned.</div>
        @else
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $order->invoice_number ?: $order->order_number }}</strong>
                        <span class="text-muted">· {{ $order->customer?->display_name ?? 'Walk-in' }}</span>
                        <span class="badge bg-success ms-1">Retail / POS</span>
                    </div>
                    <a href="{{ route('pos.receipt', $order) }}" class="btn btn-sm btn-outline-secondary">Receipt</a>
                </div>
                <div class="card-body">
                    @include('erp.sale-returns._form', [
                        'order' => $order,
                        'formAction' => route('retail-sale-returns.store'),
                        'hiddenFields' => ['order_id' => $order->id],
                        'retailMode' => true,
                    ])
                </div>
                <div class="card-footer text-end">
                    <button type="submit" form="sale-return-form-{{ $order->id }}" class="btn btn-warning">
                        Submit Retail Return
                    </button>
                </div>
            </div>
        @endif
    @endif

    @if(!$order && $recentSales->isNotEmpty())
        <div class="card shadow-sm border-0">
            <div class="card-header"><strong>Recent POS sales</strong></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th class="text-end">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSales as $sale)
                            <tr>
                                <td class="fw-semibold">{{ $sale->invoice_number ?: $sale->order_number }}</td>
                                <td>{{ $sale->customer?->display_name ?? 'Walk-in' }}</td>
                                <td class="text-muted small">{{ optional($sale->order_date ?? $sale->created_at)->format('Y-m-d H:i') }}</td>
                                <td class="text-end font-monospace">{{ number_format((float) $sale->total_amount, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('retail-sale-returns.create', ['order_id' => $sale->id]) }}" class="btn btn-sm btn-outline-warning">Return</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
