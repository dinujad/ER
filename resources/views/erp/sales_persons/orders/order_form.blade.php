<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ pathinfo($order->orderFormFilename(), PATHINFO_FILENAME) }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; }
        .header { margin-bottom: 18px; }
        .title { font-size: 22px; font-weight: 700; letter-spacing: 1px; margin-bottom: 4px; }
        .company { font-size: 16px; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 4px; }
        .muted { color: #666; }
        .box { border: 1px solid #ddd; padding: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f3f3f3; text-align: left; }
        .text-right { text-align: right; }
        .footer { margin-top: 24px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">ORDER FORM</div>
        @if($order->is_urgent)
            <div style="color:#b91c1c;font-weight:700;letter-spacing:1px;">URGENT</div>
        @endif
        <div class="company">DERMAS APPAREL SALES</div>
        <div class="muted">
            Tel: 0572052557, 0771039230, 0572225187<br>
            Email: info@dermasapparel.com<br>
            Web: www.dermasapparel.com
        </div>
    </div>

    <div class="box">
        <table style="border: 0; margin-top: 0;">
            <tr>
                <td style="border: 0; padding: 2px 0;"><strong>Order No:</strong> {{ $order->order_number }}</td>
                <td style="border: 0; padding: 2px 0;" class="text-right"><strong>Date:</strong> {{ optional($order->order_date)->format('Y-m-d') ?? $order->created_at->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td style="border: 0; padding: 2px 0;">
                    <strong>Business name:</strong> {{ filled($order->customer?->business_name) ? $order->customer->business_name : 'N/A' }}
                    @php
                        $businessAddress = trim((string) ($order->customer?->business_address ?? ''));
                    @endphp
                    @if($businessAddress !== '')
                        <br><strong>Business address:</strong> {{ $businessAddress }}
                    @endif
                </td>
                <td style="border: 0; padding: 2px 0;" class="text-right">
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
                <th style="width: 18%;">Design No</th>
                <th style="width: 42%;">Item</th>
                <th style="width: 12%;" class="text-right">Price</th>
                <th style="width: 10%;" class="text-right">Qty</th>
                <th style="width: 18%;" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                @php
                    $itemName = $item->product?->name ?? 'Product';
                    $variationName = trim((string) ($item->variation?->variation_name ?? ''));
                    if ($variationName !== '') {
                        $itemName .= ' (' . $variationName . ')';
                    }
                @endphp
                <tr>
                    <td><strong>{{ $item->product?->sku ?? '—' }}</strong></td>
                    <td>{{ $itemName }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">Subtotal</th>
                <th class="text-right">{{ number_format($subtotal, 2) }}</th>
            </tr>
            @if(($order->discount_amount ?? 0) > 0)
            <tr>
                <th colspan="4" class="text-right">Discount @if(($order->discount_type ?? '') === 'percentage')({{ number_format($order->discount_value, 2) }}%)@endif</th>
                <th class="text-right">-{{ number_format($order->discount_amount, 2) }}</th>
            </tr>
            @endif
            <tr>
                <th colspan="4" class="text-right">Total</th>
                <th class="text-right">{{ number_format($order->total_amount, 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="footer muted">
        This is a sales order form generated at order creation time.
    </div>
</body>
</html>
