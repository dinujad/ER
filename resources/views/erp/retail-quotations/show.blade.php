@extends('erp.layout')

@section('title', 'Quotation ' . $order->order_number)

@section('content')
<style>
    @media print {
        .sidebar, .navbar, .no-print, .erp-sidebar, aside, header { display: none !important; }
        .quotation-paper { box-shadow: none !important; border: 0 !important; }
    }
</style>
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
        <div>
            <h1 class="h4 mb-1">{{ $order->order_number }}</h1>
            <p class="text-muted small mb-0">Retail / POS quotation · not a sale yet</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
            <form method="POST" action="{{ route('retail-quotations.open-pos', $order) }}">
                @csrf
                <button type="submit" class="btn btn-info btn-sm text-white">Open in POS</button>
            </form>
            <a href="{{ route('retail-quotations.index') }}" class="btn btn-outline-secondary btn-sm">All quotations</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success no-print">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0 quotation-paper">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <div class="text-uppercase small text-muted fw-semibold">Quotation</div>
                    <div class="fs-5 fw-bold">{{ $order->order_number }}</div>
                    <div class="text-muted small">{{ optional($order->order_date)->format('d M Y, h:i A') }}</div>
                </div>
                <div class="text-md-end">
                    <div class="text-muted small">Customer</div>
                    <div class="fw-semibold">{{ $order->customer?->display_name ?? 'Walk-in' }}</div>
                    @if($order->customer?->phone)
                        <div class="small text-muted">{{ $order->customer->phone }}</div>
                    @endif
                    <div class="small text-muted mt-1">{{ $order->store?->name ?? 'Retail POS' }}</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit</th>
                            <th class="text-end">Line</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>
                                    {{ $item->product?->name ?? '—' }}
                                    @if($item->variation)
                                        <small class="d-block text-muted">({{ $item->variation->variation_name }})</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ number_format((float) $item->quantity, 2) }}</td>
                                <td class="text-end font-monospace">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="text-end font-monospace">{{ number_format((float) $item->total_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Subtotal</th>
                            <th class="text-end font-monospace">{{ number_format((float) $order->subtotal, 2) }}</th>
                        </tr>
                        @if((float) $order->discount_amount > 0)
                            <tr>
                                <th colspan="3" class="text-end">Discount</th>
                                <th class="text-end font-monospace">−{{ number_format((float) $order->discount_amount, 2) }}</th>
                            </tr>
                        @endif
                        @if((float) $order->shipping > 0)
                            <tr>
                                <th colspan="3" class="text-end">Shipping</th>
                                <th class="text-end font-monospace">{{ number_format((float) $order->shipping, 2) }}</th>
                            </tr>
                        @endif
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end font-monospace fs-5">Rs {{ number_format((float) $order->total_amount, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="small text-muted mt-3 mb-0">This is a quotation only. Stock is not reserved until the sale is completed in POS.</p>
        </div>
    </div>
</div>
@endsection
