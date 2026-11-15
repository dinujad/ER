@extends('erp.layout')

@section('title', 'Order History')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <h3 class="h5 mb-0 fw-bold">Order History</h3>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">Orders</a>
                <a href="{{ route('orders.history', ['date' => $date]) }}" class="btn btn-sm btn-primary">Order History</a>
            </div>
        </div>
        @if($orders->total() > 0)
            <a href="{{ route('orders.history.pdf', ['date' => $date]) }}" class="btn btn-sm btn-outline-dark">
                <i class="bi bi-file-earmark-pdf me-1"></i> Download day PDF
            </a>
        @endif
    </div>
    <div class="card-body">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="GET" action="{{ route('orders.history') }}" class="row g-2 align-items-end mb-4">
            <div class="col-sm-4 col-md-3">
                <label for="history_date" class="form-label">Date</label>
                <input type="date" id="history_date" name="date" class="form-control" value="{{ $date }}" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Show</button>
            </div>
            <div class="col-auto text-muted small">
                {{ $orders->total() }} order{{ $orders->total() === 1 ? '' : 's' }} on {{ $date }}
            </div>
        </form>

        @if(!$order)
            <div class="text-muted">No orders on this date.</div>
        @else
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <div class="fw-bold">
                        {{ $order->order_number }}
                        @if($order->is_urgent)
                            <span class="badge bg-danger">Urgent</span>
                        @endif
                    </div>
                    <div>
                        <strong>Business:</strong>
                        {{ filled($order->customer?->business_name) ? $order->customer->business_name : ($order->customer?->display_name ?? 'N/A') }}
                    </div>
                    @if($order->customer?->name && filled($order->customer?->business_name) && strcasecmp($order->customer->business_name, $order->customer->name) !== 0)
                        <div><strong>Owner:</strong> {{ $order->customer->name }}</div>
                    @endif
                    @php
                        $businessAddress = trim((string) ($order->customer?->business_address ?? ''));
                        $ownerAddress = trim((string) ($order->customer?->address ?? ''));
                    @endphp
                    @if($businessAddress !== '')
                        <div><strong>Address:</strong> {{ $businessAddress }}</div>
                    @elseif($ownerAddress !== '')
                        <div><strong>Address:</strong> {{ $ownerAddress }}</div>
                    @endif
                    <div class="text-muted small">
                        Sales person: {{ ($order->salesPerson && $order->salesPerson->role === 'sales_person') ? $order->salesPerson->name : '—' }}
                        · Status: {{ ucfirst($order->status) }}
                    </div>
                    @if(filled($order->notes))
                        <div class="mt-1"><strong>Note:</strong> <span style="white-space: pre-wrap;">{{ $order->notes }}</span></div>
                    @endif
                </div>
                <div class="text-muted small">Order {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Design No</th>
                            <th>Product</th>
                            <th>Variation</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->items as $item)
                            <tr>
                                <td><strong>{{ $item->product?->sku ?: '—' }}</strong></td>
                                <td>{{ $item->product?->name ?? 'Product' }}</td>
                                <td>{{ $item->variation?->variation_name ?: '—' }}</td>
                                <td class="text-end">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                <td class="text-end">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $item->total_price, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">No items on this order.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        @php
                            $historySubtotal = (float) $order->items->sum('total_price');
                            $historyDiscount = (float) ($order->discount_amount ?? 0);
                        @endphp
                        @if($historyDiscount > 0)
                            <tr>
                                <th colspan="5" class="text-end">Subtotal</th>
                                <th class="text-end">{{ number_format($historySubtotal, 2) }}</th>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end">Discount</th>
                                <th class="text-end">-{{ number_format($historyDiscount, 2) }}</th>
                            </tr>
                        @endif
                        <tr>
                            <th colspan="5" class="text-end">Total</th>
                            <th class="text-end">{{ number_format((float) $order->total_amount, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($orders->lastPage() > 1)
                <div class="d-flex justify-content-center mt-3">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
