<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders {{ $date }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; }
        .title { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
        .muted { color: #666; }
        .box { border: 1px solid #ddd; padding: 10px; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f3f3f3; text-align: left; }
        .text-right { text-align: right; }
        .meta td { border: 0; padding: 2px 0; }
    </style>
</head>
<body>
    @foreach($orders as $index => $order)
        <div class="order-page" @if(!$loop->last) style="page-break-after: always;" @endif>
            <div class="title">
                ORDER {{ $order->order_number }}
                @if($order->is_urgent)
                    <span style="color:#b91c1c;"> · URGENT</span>
                @endif
            </div>
            <div class="muted">{{ $date }} · {{ $index + 1 }} of {{ $orders->count() }}</div>

            <div class="box">
                <table class="meta">
                    <tr>
                        <td>
                            <strong>Business:</strong>
                            {{ filled($order->customer?->business_name) ? $order->customer->business_name : ($order->customer?->display_name ?? 'N/A') }}
                            @if($order->customer?->name && filled($order->customer?->business_name) && strcasecmp($order->customer->business_name, $order->customer->name) !== 0)
                                <br><strong>Owner:</strong> {{ $order->customer->name }}
                            @endif
                            @php
                                $businessAddress = trim((string) ($order->customer?->business_address ?? ''));
                                $ownerAddress = trim((string) ($order->customer?->address ?? ''));
                            @endphp
                            @if($businessAddress !== '')
                                <br><strong>Address:</strong> {{ $businessAddress }}
                            @elseif($ownerAddress !== '')
                                <br><strong>Address:</strong> {{ $ownerAddress }}
                            @endif
                        </td>
                        <td class="text-right">
                            <strong>Sales Person:</strong> {{ $order->salesPerson?->name ?? 'N/A' }}
                            @if(filled($order->salesPerson?->phone))
                                <br><strong>Tel:</strong> {{ $order->salesPerson->phone }}
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 16%;">Design No</th>
                        <th style="width: 28%;">Product</th>
                        <th style="width: 22%;">Variation</th>
                        <th style="width: 10%;" class="text-right">Qty</th>
                        <th style="width: 12%;" class="text-right">Price</th>
                        <th style="width: 12%;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td><strong>{{ $item->product?->sku ?? '—' }}</strong></td>
                            <td>{{ $item->product?->name ?? 'Product' }}</td>
                            <td>{{ $item->variation?->variation_name ?: '—' }}</td>
                            <td class="text-right">{{ number_format((float) $item->quantity, 0) }}</td>
                            <td class="text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="text-right">{{ number_format((float) $item->total_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @php
                        $historySubtotal = (float) $order->items->sum('total_price');
                        $historyDiscount = (float) ($order->discount_amount ?? 0);
                    @endphp
                    @if($historyDiscount > 0)
                        <tr>
                            <th colspan="5" class="text-right">Subtotal</th>
                            <th class="text-right">{{ number_format($historySubtotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-right">Discount</th>
                            <th class="text-right">-{{ number_format($historyDiscount, 2) }}</th>
                        </tr>
                    @endif
                    <tr>
                        <th colspan="5" class="text-right">Total</th>
                        <th class="text-right">{{ number_format((float) $order->total_amount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endforeach
</body>
</html>
