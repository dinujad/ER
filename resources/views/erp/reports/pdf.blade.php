<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - {{ config('app.name') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h2 { font-size: 14px; margin-bottom: 8px; }
        .meta { color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    <div class="meta">{{ config('app.name') }} | Period: {{ $from }} to {{ $to }} | Generated: {{ now()->format('d M Y H:i') }}</div>

    @if($report === 'profit-loss')
        <table>
            <tr><th>Total Revenue</th><td class="text-right">Rs {{ number_format($data['total_revenue'] ?? 0, 2) }}</td></tr>
            <tr><th>Total Cost</th><td class="text-right">Rs {{ number_format($data['total_cost'] ?? 0, 2) }}</td></tr>
            <tr><th>Profit</th><td class="text-right">Rs {{ number_format($data['profit'] ?? 0, 2) }}</td></tr>
            <tr><th>Orders Count</th><td class="text-right">{{ $data['orders_count'] ?? 0 }}</td></tr>
        </table>
    @elseif($report === 'stock')
        <table>
            <tr><th>Product</th><th>SKU</th><th>Current Stock</th><th>Unit</th></tr>
            @foreach($data['products'] ?? [] as $p)
                <tr><td>{{ $p->name }}</td><td>{{ $p->sku }}</td><td>{{ $p->current_stock ?? 0 }}</td><td>{{ $p->unit?->short_name ?? '' }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'purchase-payment')
        <p><strong>Total: Rs {{ number_format($data['total'] ?? 0, 2) }}</strong> | Count: {{ $data['count'] ?? 0 }}</p>
        <table>
            <tr><th>Date</th><th>Supplier</th><th>PO / Ref</th><th>Amount (Rs)</th><th>Method</th><th>Reference</th></tr>
            @foreach($data['payments'] ?? [] as $p)
                <tr><td>{{ $p->payment_date?->format('Y-m-d') }}</td><td>{{ $p->supplier?->name ?? '—' }}</td><td>{{ $p->purchase?->purchase_no ?? '—' }}</td><td class="text-right">{{ number_format($p->amount, 2) }}</td><td>{{ ucfirst(str_replace('_', ' ', $p->payment_method ?? '—')) }}</td><td>{{ $p->reference ?? '—' }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'sell-payment')
        <p><strong>Total Collected: Rs {{ number_format($data['total'] ?? 0, 2) }}</strong></p>
        <table>
            <tr><th>Date</th><th>Order</th><th>Amount</th><th>Method</th><th>Cash discount</th></tr>
            @foreach($data['payments'] ?? [] as $p)
                <tr><td>{{ $p->payment_date?->format('Y-m-d') }}</td><td>{{ $p->order?->order_number }}</td><td>{{ number_format($p->amount, 2) }}</td><td>{{ $p->payment_method ?? '—' }}</td><td>{{ $p->cashNextPaymentDiscountLine() ?? '—' }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'sales-representative')
        <p><strong>Sales Person wise</strong> | Count: {{ count($data['reps'] ?? []) }} | Total Sales: Rs {{ number_format(collect($data['reps'] ?? [])->sum('total_sales'), 2) }}</p>
        <table>
            <tr><th>#</th><th>Sales Person</th><th>Order Count</th><th>Total Sales (Rs)</th></tr>
            @foreach($data['reps'] ?? [] as $idx => $r)
                <tr><td>{{ $idx + 1 }}</td><td>{{ $r->salesPerson?->name ?? 'N/A' }}</td><td>{{ $r->order_count }}</td><td class="text-right">{{ number_format($r->total_sales, 2) }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'product-sell')
        <table>
            <tr><th>Product</th><th>Quantity Sold</th><th>Total Amount (Rs)</th></tr>
            @foreach($data['items'] ?? [] as $i)
                <tr><td>{{ $i->product?->name ?? 'N/A' }}</td><td>{{ $i->total_qty }}</td><td>{{ number_format($i->total_amount, 2) }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'supplier')
        <p><strong>Count: {{ $data['count'] ?? 0 }}</strong></p>
        <table>
            <tr><th>Code</th><th>Supplier</th><th>Company</th><th>Phone</th><th>Total Purchases (Rs)</th><th>Total Paid (Rs)</th><th>Outstanding (Rs)</th></tr>
            @foreach($data['suppliers'] ?? [] as $s)
                <tr><td>{{ $s->code ?? '—' }}</td><td>{{ $s->name }}</td><td>{{ $s->company_name ?? '—' }}</td><td>{{ $s->phone ?? '—' }}</td><td class="text-right">{{ number_format($s->total_purchases ?? 0, 2) }}</td><td class="text-right">{{ number_format($s->total_paid ?? 0, 2) }}</td><td class="text-right">{{ number_format($s->outstanding_balance ?? 0, 2) }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'customer')
        <p><strong>Count: {{ $data['count'] ?? 0 }}</strong> | Total Sales: Rs {{ number_format($data['total_sales'] ?? 0, 2) }}</p>
        <table>
            <tr><th>Customer</th><th>Phone</th><th>Email</th><th>Orders</th><th>Total Sales (Rs)</th></tr>
            @foreach($data['customers'] ?? [] as $c)
                <tr><td>{{ $c->display_name }}</td><td>{{ $c->phone ?? '—' }}</td><td>{{ $c->email ?? '—' }}</td><td>{{ $c->orders_count ?? 0 }}</td><td class="text-right">{{ number_format($c->total_sales ?? 0, 2) }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'supplier-customer')
        <table>
            <tr><th>Customer</th><th>Phone</th><th>Orders Count</th></tr>
            @foreach($data['customers'] ?? [] as $c)
                <tr><td>{{ $c->display_name }}</td><td>{{ $c->phone ?? '—' }}</td><td>{{ $c->orders_count ?? 0 }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'stock-adjustment')
        <table>
            <tr><th>Date</th><th>Product</th><th>Quantity</th><th>Notes</th><th>By</th></tr>
            @foreach($data['ledgers'] ?? [] as $l)
                <tr><td>{{ $l->created_at->format('Y-m-d H:i') }}</td><td>{{ $l->product?->name }}</td><td>{{ $l->quantity }}</td><td>{{ $l->notes ?? '—' }}</td><td>{{ $l->creator?->name ?? '—' }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'trending-products')
        <p>Channel: {{ $data['filters']['channel_label'] ?? 'All' }}</p>
        <table>
            <tr><th>#</th><th>Product</th><th>Qty</th><th>POS Retail</th><th>Wholesale</th><th>Total Sales (Rs)</th></tr>
            @foreach($data['rows'] ?? [] as $row)
                <tr>
                    <td>{{ $row['rank'] ?? '' }}</td>
                    <td>{{ $row['product']->name ?? 'N/A' }}</td>
                    <td>{{ $row['total_qty'] ?? 0 }}</td>
                    <td>{{ $row['retail_qty'] ?? 0 }}</td>
                    <td>{{ $row['wholesale_qty'] ?? 0 }}</td>
                    <td>{{ number_format($row['total_sales'] ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </table>
    @elseif($report === 'purchase')
        <p><strong>Total: Rs {{ number_format($data['total'] ?? 0, 2) }}</strong> | Paid: Rs {{ number_format($data['total_paid'] ?? 0, 2) }} | Count: {{ $data['count'] ?? 0 }}</p>
        <table>
            <tr><th>Date</th><th>PO #</th><th>Supplier</th><th>Status</th><th>Total (Rs)</th><th>Paid (Rs)</th><th>Balance (Rs)</th></tr>
            @foreach($data['purchases'] ?? [] as $p)
                <tr><td>{{ $p->date?->format('Y-m-d') }}</td><td>{{ $p->purchase_no }}</td><td>{{ $p->supplier?->name ?? '—' }}</td><td>{{ $p->status }}</td><td class="text-right">{{ number_format($p->total, 2) }}</td><td class="text-right">{{ number_format($p->total_paid, 2) }}</td><td class="text-right">{{ number_format($p->balance, 2) }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'sale')
        <p>
            <strong>Total: Rs {{ number_format($data['total'] ?? 0, 2) }}</strong> |
            Paid: Rs {{ number_format($data['total_paid'] ?? 0, 2) }} |
            Due: Rs {{ number_format($data['total_due'] ?? 0, 2) }} |
            Count: {{ $data['count'] ?? 0 }}
        </p>
        <h3 style="font-size:12px;margin:12px 0 6px;">Business summary</h3>
        <table>
            <tr><th>Business name</th><th>Orders</th><th>Total (Rs)</th><th>Paid (Rs)</th><th>Due (Rs)</th></tr>
            @foreach($data['byBusiness'] ?? [] as $row)
                <tr>
                    <td>{{ $row->label }}</td>
                    <td class="text-right">{{ $row->order_count }}</td>
                    <td class="text-right">{{ number_format($row->total_sales, 2) }}</td>
                    <td class="text-right">{{ number_format($row->total_paid, 2) }}</td>
                    <td class="text-right">{{ number_format($row->total_due, 2) }}</td>
                </tr>
            @endforeach
        </table>
        <h3 style="font-size:12px;margin:12px 0 6px;">Sales person summary</h3>
        <table>
            <tr><th>Sales person</th><th>Orders</th><th>Total (Rs)</th><th>Paid (Rs)</th><th>Due (Rs)</th></tr>
            @foreach($data['bySalesPerson'] ?? [] as $row)
                <tr>
                    <td>{{ $row->label }}</td>
                    <td class="text-right">{{ $row->order_count }}</td>
                    <td class="text-right">{{ number_format($row->total_sales, 2) }}</td>
                    <td class="text-right">{{ number_format($row->total_paid, 2) }}</td>
                    <td class="text-right">{{ number_format($row->total_due, 2) }}</td>
                </tr>
            @endforeach
        </table>
        <h3 style="font-size:12px;margin:12px 0 6px;">Orders</h3>
        <table>
            <tr><th>Date</th><th>Order #</th><th>Business name</th><th>Type</th><th>Sales Person</th><th>Status</th><th>Total</th><th>Paid</th><th>Due</th></tr>
            @foreach($data['orders'] ?? [] as $o)
                @php
                    $paid = (float) ($o->payments_sum_amount ?? 0);
                    $total = (float) ($o->total_amount ?? 0);
                    $isWholesale = $o->salesPerson && $o->salesPerson->role === 'sales_person';
                @endphp
                <tr>
                    <td>{{ ($o->order_date ?? $o->created_at)?->format('Y-m-d') }}</td>
                    <td>{{ $o->order_number }}</td>
                    <td>{{ filled($o->customer?->business_name) ? $o->customer->business_name : ($o->customer?->display_name ?? '—') }}</td>
                    <td>{{ $isWholesale ? 'Wholesale' : 'Retail' }}</td>
                    <td>{{ $isWholesale ? ($o->salesPerson->name ?? '—') : '—' }}</td>
                    <td>{{ $o->status }}</td>
                    <td class="text-right">{{ number_format($total, 2) }}</td>
                    <td class="text-right">{{ number_format($paid, 2) }}</td>
                    <td class="text-right">{{ number_format(max(0, $total - $paid), 2) }}</td>
                </tr>
            @endforeach
        </table>
    @elseif($report === 'purchase-sale')
        <p><strong>Total: Rs {{ number_format($data['total'] ?? 0, 2) }}</strong></p>
        <table>
            <tr><th>Date</th><th>Order #</th><th>Customer</th><th>Amount (Rs)</th></tr>
            @foreach($data['orders'] ?? [] as $o)
                <tr><td>{{ $o->order_date?->format('Y-m-d') }}</td><td>{{ $o->order_number }}</td><td>{{ $o->customer?->display_name ?? '—' }}</td><td>{{ number_format($o->total_amount, 2) }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'register')
        <p>Total Sales: Rs {{ number_format($data['total_sales'] ?? 0, 2) }} | Total Collected: Rs {{ number_format($data['total_collected'] ?? 0, 2) }}</p>
        <table>
            <tr><th>Date</th><th>Order #</th><th>Customer</th><th>Amount (Rs)</th></tr>
            @foreach($data['orders'] ?? [] as $o)
                <tr><td>{{ $o->order_date?->format('Y-m-d') }}</td><td>{{ $o->order_number }}</td><td>{{ $o->customer?->display_name ?? '—' }}</td><td>{{ number_format($o->total_amount, 2) }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'activity-log')
        <table>
            <tr><th>Date</th><th>Order #</th><th>Customer</th><th>Sales Person</th><th>Amount (Rs)</th></tr>
            @foreach($data['orders'] ?? [] as $o)
                <tr><td>{{ $o->order_date?->format('Y-m-d H:i') }}</td><td>{{ $o->order_number }}</td><td>{{ $o->customer?->display_name ?? '—' }}</td><td>{{ $o->salesPerson?->name ?? '—' }}</td><td>{{ number_format($o->total_amount, 2) }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'items')
        <table>
            <tr><th>Product</th><th>SKU</th><th>Category</th><th>Stock</th><th>Unit</th></tr>
            @foreach($data['products'] ?? [] as $p)
                <tr><td>{{ $p->name }}</td><td>{{ $p->sku }}</td><td>{{ $p->category?->name ?? '—' }}</td><td>{{ $p->current_stock ?? 0 }}</td><td>{{ $p->unit?->short_name ?? '' }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'expense')
        <p><strong>Total: Rs {{ number_format($data['total'] ?? 0, 2) }}</strong></p>
        <table>
            <tr><th>Date</th><th>Category</th><th>Amount</th><th>Reference</th><th>Note</th><th>Added By</th></tr>
            @foreach($data['expenses'] ?? [] as $e)
                <tr><td>{{ $e->expense_date?->format('Y-m-d') }}</td><td>{{ $e->category?->name }}</td><td>{{ number_format($e->amount, 2) }}</td><td>{{ $e->reference ?? '—' }}</td><td>{{ $e->note ?? '—' }}</td><td>{{ $e->creator?->name ?? '—' }}</td></tr>
            @endforeach
        </table>
    @elseif($report === 'sales-person-expenses')
        <p><strong>Total: Rs {{ number_format($data['total'] ?? 0, 2) }}</strong> | Lines: {{ $data['count'] ?? 0 }} | With receipt: {{ $data['withSlipCount'] ?? 0 }}</p>
        <h3>By sales person</h3>
        <table>
            <tr><th>Sales Person</th><th>Lines</th><th>With Receipt</th><th>Total (Rs)</th></tr>
            @foreach($data['byPerson'] ?? [] as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->count }}</td>
                    <td>{{ $row->with_slip }}</td>
                    <td class="text-right">{{ number_format($row->total, 2) }}</td>
                </tr>
            @endforeach
        </table>
        <h3>By type</h3>
        <table>
            <tr><th>Type</th><th>Lines</th><th>Total (Rs)</th></tr>
            @foreach($data['byType'] ?? [] as $row)
                <tr>
                    <td>{{ $row->label }}</td>
                    <td>{{ $row->count }}</td>
                    <td class="text-right">{{ number_format($row->total, 2) }}</td>
                </tr>
            @endforeach
        </table>
        <h3>Expense lines</h3>
        <table>
            <tr><th>Date</th><th>Sales Person</th><th>Type</th><th>Description</th><th>Route</th><th>Amount (Rs)</th><th>Receipt</th></tr>
            @foreach($data['expenses'] ?? [] as $expense)
                <tr>
                    <td>{{ $expense->expense_date?->format('Y-m-d') }}</td>
                    <td>{{ $expense->user?->name ?? '—' }}</td>
                    <td>{{ $expense->typeLabel() }}</td>
                    <td>{{ $expense->description }}</td>
                    <td>{{ $expense->routeRun?->salesRoute?->pathLabel() ?? '—' }}</td>
                    <td class="text-right">{{ number_format((float) $expense->amount, 2) }}</td>
                    <td>{{ $expense->slip_path ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <p>{{ $data['message'] ?? 'No data for this report.' }}</p>
    @endif
</body>
</html>
