<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt — {{ $order->order_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            body, .receipt-paper { width: 80mm !important; max-width: 80mm !important; min-width: 80mm !important; }
        }
        body { margin: 0 auto; padding: 12px; font-family: monospace; font-size: 12px; }
        .receipt-paper { width: 80mm; max-width: 80mm; margin: 0 auto; }
        .receipt-header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 8px; margin-bottom: 8px; }
        .receipt-title { font-size: 14px; font-weight: 700; }
        .receipt-meta { font-size: 11px; color: #333; }
        table { width: 100%; font-size: 11px; }
        .row-total { font-weight: 700; border-top: 1px dashed #000; }
        .payments-list { margin-top: 8px; font-size: 11px; }
        .receipt-line { margin: 2px 0; }
    </style>
</head>
<body>
    <div class="receipt-paper">
    @if(session('success'))
    <div class="alert alert-success no-print">{{ session('success') }}</div>
    @endif
    <div class="receipt-header">
        <div class="receipt-title">{{ config('app.name') }}</div>
        <div class="receipt-meta">POS Receipt</div>
    </div>
    <p class="mb-1"><strong>Order:</strong> {{ $order->order_number }}</p>
    <p class="mb-1"><strong>Date:</strong> {{ $order->order_date->format('d M Y, h:i A') }}</p>
    <p class="mb-2"><strong>Customer:</strong> {{ $order->customer->name ?? 'Walk-in' }}</p>

    <table class="table table-sm">
        <thead>
            <tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product->name ?? '—' }}{{ $item->variation ? ' (' . $item->variation->variation_name . ')' : '' }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-end">{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($order->discount_amount > 0)
    <p class="mb-0 text-end">Discount: - {{ number_format($order->discount_amount, 2) }}</p>
    @endif
    <p class="row-total text-end mb-0 pt-2">Total: LKR {{ number_format($order->total_amount, 2) }}</p>

    @if($order->payments->isNotEmpty())
    <div class="payments-list">
        <strong>Payments:</strong>
        @foreach($order->payments as $p)
        <div>{{ ucfirst($p->payment_method) }}: LKR {{ number_format($p->amount, 2) }}</div>
        @endforeach
    </div>
    @endif

    @if(isset($amountTendered) && $amountTendered !== null)
    <div class="receipt-line mt-2"><strong>Amount given:</strong> LKR {{ number_format($amountTendered, 2) }}</div>
    @endif
    @if(isset($change) && $change !== null)
    <div class="receipt-line"><strong>Change:</strong> LKR {{ number_format($change, 2) }}</div>
    @endif

    <div class="mt-4 pt-3 border-top text-center receipt-meta">Thank you</div>
    </div>

    <div class="no-print mt-4 d-flex gap-2 justify-content-center flex-wrap">
        <button type="button" class="btn btn-primary" onclick="window.print();"><i class="bi bi-printer"></i> Print</button>
        <a href="{{ route('pos.receipt.pdf', $order) }}" class="btn btn-outline-primary" target="_blank"><i class="bi bi-file-pdf"></i> Download PDF</a>
        @if($order->canAcceptSaleReturn())
            <a href="{{ route('retail-sale-returns.create', ['order_id' => $order->id]) }}" class="btn btn-outline-warning"><i class="bi bi-arrow-counterclockwise"></i> Sale Return</a>
        @endif
        <a href="{{ route('pos.index') }}" class="btn btn-outline-secondary">Back to POS</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</body>
</html>
