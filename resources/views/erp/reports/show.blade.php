@extends('erp.layout')

@section('content')
<style>
@media print {
    .sidebar, .erp-mobile-header, .no-print, .btn, .dropdown, nav, .alert { display: none !important; }
    .content { padding: 0 !important; }
    .card { border: 1px solid #dee2e6; box-shadow: none !important; }
    body { background: #fff !important; }
}
</style>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm mb-2 no-print"><i class="bi bi-arrow-left me-1"></i> Back to Reports</a>
            <h4 class="mb-0 fw-bold">{{ $title }}</h4>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if($report !== 'sale' && $report !== 'expense')
            <form action="{{ route('reports.show', $report) }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm" style="max-width: 140px;">
                <span class="text-muted">to</span>
                <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm" style="max-width: 140px;">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            </form>
            @endif
            <button type="button" class="btn btn-outline-secondary btn-sm no-print" onclick="window.print();"><i class="bi bi-printer me-1"></i> Print</button>
            <div class="dropdown">
                <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">Export</button>
                <ul class="dropdown-menu">
                    @php
                        $exportParams = array_merge(
                            ['report' => $report, 'format' => 'pdf', 'from' => $from, 'to' => $to],
                            $report === 'sale' ? array_filter([
                                'sale_type' => $data['filters']['sale_type'] ?? null,
                                'sales_person_id' => $data['filters']['sales_person_id'] ?? null,
                                'customer_id' => $data['filters']['customer_id'] ?? null,
                                'status' => $data['filters']['status'] ?? null,
                                'payment_state' => $data['filters']['payment_state'] ?? null,
                            ]) : []
                        );
                    @endphp
                    <li><a class="dropdown-item" href="{{ route('reports.export', $exportParams) }}"><i class="bi bi-file-pdf me-2"></i> Export PDF</a></li>
                    <li><a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'csv'])) }}"><i class="bi bi-file-earmark-excel me-2"></i> Export Excel (CSV)</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if(!empty($data['message']))
                <p class="text-muted">{{ $data['message'] }}</p>
            @endif

            @switch($report)
                @case('profit-loss')
                    <div class="row g-3 mb-4">
                        <div class="col-md-3"><div class="p-3 bg-light rounded"><strong>Total Revenue</strong><div class="fs-4">Rs {{ number_format($data['total_revenue'] ?? 0, 2) }}</div></div></div>
                        <div class="col-md-3"><div class="p-3 bg-light rounded"><strong>Total Cost</strong><div class="fs-4">Rs {{ number_format($data['total_cost'] ?? 0, 2) }}</div></div></div>
                        <div class="col-md-3"><div class="p-3 bg-success bg-opacity-10 rounded"><strong>Profit</strong><div class="fs-4 text-success">Rs {{ number_format($data['profit'] ?? 0, 2) }}</div></div></div>
                        <div class="col-md-3"><div class="p-3 bg-light rounded"><strong>Orders</strong><div class="fs-4">{{ $data['orders_count'] ?? 0 }}</div></div></div>
                    </div>
                    @break

                @case('stock')
                    <table class="table table-bordered">
                        <thead><tr><th>Product</th><th>SKU</th><th>Current Stock</th><th>Unit</th></tr></thead>
                        <tbody>
                            @forelse(($data['products'] ?? []) as $p)
                                <tr><td>{{ $p->name }}</td><td>{{ $p->sku }}</td><td>{{ $p->current_stock ?? 0 }}</td><td>{{ $p->unit?->short_name ?? '' }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No products.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('purchase-payment')
                    <div class="mb-3 no-print"><strong>Total: Rs {{ number_format($data['total'] ?? 0, 2) }}</strong> | Count: {{ $data['count'] ?? 0 }}</div>
                    <table class="table table-bordered">
                        <thead><tr><th>Date</th><th>Supplier</th><th>PO / Ref</th><th>Amount (Rs)</th><th>Method</th><th>Reference</th></tr></thead>
                        <tbody>
                            @forelse(($data['payments'] ?? []) as $p)
                                <tr>
                                    <td>{{ $p->payment_date?->format('d M Y') }}</td>
                                    <td>{{ $p->supplier?->name ?? '—' }}</td>
                                    <td>{{ $p->purchase?->purchase_no ?? '—' }}</td>
                                    <td>{{ number_format($p->amount, 2) }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $p->payment_method ?? '—')) }}</td>
                                    <td>{{ $p->reference ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">No purchase payments in date range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('sell-payment')
                    <p class="mb-2"><strong>Total Collected: Rs {{ number_format($data['total'] ?? 0, 2) }}</strong></p>
                    <table class="table table-bordered">
                        <thead><tr><th>Date</th><th>Order</th><th>Amount</th><th>Method</th><th>Reference</th><th>Cash discount</th></tr></thead>
                        <tbody>
                            @forelse(($data['payments'] ?? []) as $p)
                                <tr>
                                    <td>{{ $p->payment_date?->format('d M Y') }}</td>
                                    <td>{{ $p->order?->order_number }}</td>
                                    <td>{{ number_format($p->amount, 2) }}</td>
                                    <td>{{ $p->payment_method ?? '—' }}</td>
                                    <td>{{ $p->reference_number ?? '—' }}</td>
                                    <td>{{ $p->cashNextPaymentDiscountLine() ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">No payments in date range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('sales-representative')
                    <div class="mb-3 no-print">
                        <strong>Sales Person wise</strong> |
                        Count: {{ count($data['reps'] ?? []) }} |
                        Total Sales: Rs {{ number_format(collect($data['reps'] ?? [])->sum('total_sales'), 2) }}
                    </div>
                    <table class="table table-bordered">
                        <thead><tr><th>#</th><th>Sales Person</th><th>Order Count</th><th>Total Sales (Rs)</th></tr></thead>
                        <tbody>
                            @forelse(($data['reps'] ?? []) as $idx => $r)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>{{ $r->salesPerson?->name ?? 'N/A' }}</td>
                                    <td>{{ $r->order_count }}</td>
                                    <td>{{ number_format($r->total_sales, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No sales person data in date range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('product-sell')
                    <table class="table table-bordered">
                        <thead><tr><th>Product</th><th>Quantity Sold</th><th>Total Amount (Rs)</th></tr></thead>
                        <tbody>
                            @forelse(($data['items'] ?? []) as $i)
                                <tr><td>{{ $i->product?->name ?? 'N/A' }}</td><td>{{ $i->total_qty }}</td><td>{{ number_format($i->total_amount, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">No sales in date range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('supplier')
                    <div class="mb-3 no-print"><strong>Count: {{ $data['count'] ?? 0 }}</strong></div>
                    <table class="table table-bordered">
                        <thead><tr><th>Code</th><th>Supplier</th><th>Company</th><th>Phone</th><th>Total Purchases (Rs)</th><th>Total Paid (Rs)</th><th>Outstanding (Rs)</th></tr></thead>
                        <tbody>
                            @forelse(($data['suppliers'] ?? []) as $s)
                                <tr>
                                    <td>{{ $s->code ?? '—' }}</td>
                                    <td>{{ $s->name }}</td>
                                    <td>{{ $s->company_name ?? '—' }}</td>
                                    <td>{{ $s->phone ?? '—' }}</td>
                                    <td>{{ number_format($s->total_purchases ?? 0, 2) }}</td>
                                    <td class="text-success">{{ number_format($s->total_paid ?? 0, 2) }}</td>
                                    <td class="{{ ($s->outstanding_balance ?? 0) > 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format($s->outstanding_balance ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted">No suppliers.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('customer')
                    <div class="mb-3 no-print"><strong>Count: {{ $data['count'] ?? 0 }}</strong> | Total Sales: Rs {{ number_format($data['total_sales'] ?? 0, 2) }}</div>
                    <table class="table table-bordered">
                        <thead><tr><th>Customer</th><th>Phone</th><th>Email</th><th>Orders</th><th>Total Sales (Rs)</th></tr></thead>
                        <tbody>
                            @forelse(($data['customers'] ?? []) as $c)
                                <tr>
                                    <td>{{ $c->display_name }}</td>
                                    <td>{{ $c->phone ?? '—' }}</td>
                                    <td>{{ $c->email ?? '—' }}</td>
                                    <td>{{ $c->orders_count ?? 0 }}</td>
                                    <td>{{ number_format($c->total_sales ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No customers.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('supplier-customer')
                    <table class="table table-bordered">
                        <thead><tr><th>Customer</th><th>Phone</th><th>Email</th><th>Orders</th></tr></thead>
                        <tbody>
                            @forelse(($data['customers'] ?? []) as $c)
                                <tr><td>{{ $c->display_name }}</td><td>{{ $c->phone ?? '—' }}</td><td>{{ $c->email ?? '—' }}</td><td>{{ $c->orders_count ?? 0 }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No customers.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('stock-adjustment')
                    <table class="table table-bordered">
                        <thead><tr><th>Date</th><th>Product</th><th>Quantity</th><th>Notes</th><th>By</th></tr></thead>
                        <tbody>
                            @forelse(($data['ledgers'] ?? []) as $l)
                                <tr><td>{{ $l->created_at->format('d M Y H:i') }}</td><td>{{ $l->product?->name }}</td><td>{{ $l->quantity }}</td><td>{{ $l->notes ?? '—' }}</td><td>{{ $l->creator?->name ?? '—' }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No adjustments in date range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('trending-products')
                    <table class="table table-bordered">
                        <thead><tr><th>#</th><th>Product</th><th>Quantity Sold</th><th>Total Sales (Rs)</th></tr></thead>
                        <tbody>
                            @foreach($data['items'] ?? [] as $idx => $i)
                                <tr><td>{{ $idx + 1 }}</td><td>{{ $i->product?->name ?? 'N/A' }}</td><td>{{ $i->total_qty }}</td><td>{{ number_format($i->total_sales ?? 0, 2) }}</td></tr>
                            @endforeach
                            @if(empty($data['items']))
                                <tr><td colspan="4" class="text-muted">No sales in date range.</td></tr>
                            @endif
                        </tbody>
                    </table>
                    @break

                @case('purchase')
                    <div class="mb-3 no-print">
                        <strong>Total: Rs {{ number_format($data['total'] ?? 0, 2) }}</strong> |
                        Paid: Rs {{ number_format($data['total_paid'] ?? 0, 2) }} |
                        Count: {{ $data['count'] ?? 0 }}
                    </div>
                    <table class="table table-bordered">
                        <thead><tr><th>Date</th><th>PO #</th><th>Supplier</th><th>Status</th><th>Total (Rs)</th><th>Paid (Rs)</th><th>Balance (Rs)</th></tr></thead>
                        <tbody>
                            @forelse(($data['purchases'] ?? []) as $p)
                                <tr>
                                    <td>{{ $p->date?->format('d M Y') }}</td>
                                    <td><a href="{{ route('purchases.show', $p) }}" class="text-decoration-none">{{ $p->purchase_no }}</a></td>
                                    <td>{{ $p->supplier?->name ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $p->status === 'confirmed' ? 'success' : ($p->status === 'cancelled' ? 'danger' : 'secondary') }}">{{ ucfirst($p->status) }}</span></td>
                                    <td>{{ number_format($p->total, 2) }}</td>
                                    <td class="text-success">{{ number_format($p->total_paid, 2) }}</td>
                                    <td class="{{ $p->balance > 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format($p->balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted">No purchases in date range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('sale')
                    @include('erp.reports.partials.sale-filters')

                    <div class="row g-3 mb-3 no-print">
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded h-100">
                                <strong class="d-block">Total sales</strong>
                                <div class="fs-5">Rs {{ number_format($data['total'] ?? 0, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded h-100">
                                <strong class="d-block">Collected</strong>
                                <div class="fs-5 text-success">Rs {{ number_format($data['total_paid'] ?? 0, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded h-100">
                                <strong class="d-block">Outstanding</strong>
                                <div class="fs-5 text-danger">Rs {{ number_format($data['total_due'] ?? 0, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded h-100">
                                <strong class="d-block">Orders</strong>
                                <div class="fs-5">{{ $data['count'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-semibold mb-2">Sales person wise summary</h6>
                    <table class="table table-bordered table-sm mb-4">
                        <thead class="table-light">
                            <tr>
                                <th>Sales person</th>
                                <th class="text-end">Orders</th>
                                <th class="text-end">Total (Rs)</th>
                                <th class="text-end">Paid (Rs)</th>
                                <th class="text-end">Due (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['bySalesPerson'] ?? [] as $row)
                                <tr>
                                    <td>{{ $row->label }}</td>
                                    <td class="text-end">{{ $row->order_count }}</td>
                                    <td class="text-end">{{ number_format($row->total_sales, 2) }}</td>
                                    <td class="text-end text-success">{{ number_format($row->total_paid, 2) }}</td>
                                    <td class="text-end {{ $row->total_due > 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format($row->total_due, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No data for selected filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <h6 class="fw-semibold mb-2">Business wise summary</h6>
                    <table class="table table-bordered table-sm mb-4">
                        <thead class="table-light">
                            <tr>
                                <th>Business name</th>
                                <th class="text-end">Orders</th>
                                <th class="text-end">Total (Rs)</th>
                                <th class="text-end">Paid (Rs)</th>
                                <th class="text-end">Due (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['byBusiness'] ?? [] as $row)
                                <tr>
                                    <td>{{ $row->label }}</td>
                                    <td class="text-end">{{ $row->order_count }}</td>
                                    <td class="text-end">{{ number_format($row->total_sales, 2) }}</td>
                                    <td class="text-end text-success">{{ number_format($row->total_paid, 2) }}</td>
                                    <td class="text-end {{ $row->total_due > 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format($row->total_due, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No data for selected filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <h6 class="fw-semibold mb-2">Order details</h6>
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Order #</th>
                                <th>Business name</th>
                                <th>Type</th>
                                <th>Sales person</th>
                                <th>Status</th>
                                <th class="text-end">Total (Rs)</th>
                                <th class="text-end">Paid (Rs)</th>
                                <th class="text-end">Due (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($data['orders'] ?? []) as $o)
                                @php
                                    $paid = (float) ($o->payments_sum_amount ?? 0);
                                    $due = max(0, (float) $o->total_amount - $paid);
                                    $isWholesale = $o->salesPerson && $o->salesPerson->role === 'sales_person';
                                @endphp
                                <tr>
                                    <td>{{ ($o->order_date ?? $o->created_at)?->format('d M Y') }}</td>
                                    <td><a href="{{ route('orders.show', $o) }}" class="text-decoration-none">{{ $o->order_number }}</a></td>
                                    <td>{{ filled($o->customer?->business_name) ? $o->customer->business_name : ($o->customer?->display_name ?? '—') }}</td>
                                    <td><span class="badge bg-{{ $isWholesale ? 'dark' : 'info' }}">{{ $isWholesale ? 'Wholesale' : 'Retail' }}</span></td>
                                    <td>{{ $isWholesale ? $o->salesPerson->name : '—' }}</td>
                                    <td>{{ ucfirst($o->status) }}</td>
                                    <td class="text-end">{{ number_format($o->total_amount, 2) }}</td>
                                    <td class="text-end text-success">{{ number_format($paid, 2) }}</td>
                                    <td class="text-end {{ $due > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ number_format($due, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-muted">No sales match your filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('purchase-sale')
                    <p class="mb-2"><strong>Total: Rs {{ number_format($data['total'] ?? 0, 2) }}</strong></p>
                    <table class="table table-bordered">
                        <thead><tr><th>Date</th><th>Order #</th><th>Customer</th><th>Amount (Rs)</th></tr></thead>
                        <tbody>
                            @forelse(($data['orders'] ?? []) as $o)
                                <tr><td>{{ $o->order_date?->format('d M Y') }}</td><td>{{ $o->order_number }}</td><td>{{ $o->customer?->display_name ?? '—' }}</td><td>{{ number_format($o->total_amount, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No orders in date range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('register')
                    <p class="mb-2">Total Sales: <strong>Rs {{ number_format($data['total_sales'] ?? 0, 2) }}</strong> | Total Collected: <strong>Rs {{ number_format($data['total_collected'] ?? 0, 2) }}</strong></p>
                    <h6>Orders</h6>
                    <table class="table table-bordered mb-4">
                        <thead><tr><th>Date</th><th>Order #</th><th>Customer</th><th>Amount (Rs)</th></tr></thead>
                        <tbody>
                            @forelse(($data['orders'] ?? []) as $o)
                                <tr><td>{{ $o->order_date?->format('d M Y') }}</td><td>{{ $o->order_number }}</td><td>{{ $o->customer?->display_name ?? '—' }}</td><td>{{ number_format($o->total_amount, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No orders.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <h6>Payments</h6>
                    <table class="table table-bordered">
                        <thead><tr><th>Date</th><th>Order</th><th>Amount</th><th>Method</th></tr></thead>
                        <tbody>
                            @forelse(($data['payments'] ?? []) as $p)
                                <tr><td>{{ $p->payment_date?->format('d M Y') }}</td><td>{{ $p->order?->order_number }}</td><td>{{ number_format($p->amount, 2) }}</td><td>{{ $p->payment_method ?? '—' }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No payments.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('activity-log')
                    <table class="table table-bordered">
                        <thead><tr><th>Date</th><th>Order #</th><th>Customer</th><th>Sales Person</th><th>Amount (Rs)</th></tr></thead>
                        <tbody>
                            @forelse(($data['orders'] ?? []) as $o)
                                <tr><td>{{ $o->order_date?->format('d M Y H:i') }}</td><td>{{ $o->order_number }}</td><td>{{ $o->customer?->display_name ?? '—' }}</td><td>{{ $o->salesPerson?->name ?? '—' }}</td><td>{{ number_format($o->total_amount, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No orders in date range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('items')
                    <table class="table table-bordered">
                        <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Stock</th><th>Unit</th></tr></thead>
                        <tbody>
                            @forelse(($data['products'] ?? []) as $p)
                                <tr><td>{{ $p->name }}</td><td>{{ $p->sku }}</td><td>{{ $p->category?->name ?? '—' }}</td><td>{{ $p->current_stock ?? 0 }}</td><td>{{ $p->unit?->short_name ?? '' }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No products.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('expense')
                    @php
                        $ledgerByMonth = $data['ledger_by_month'] ?? [];
                        $monthlyTotals = $data['monthly_totals'] ?? [];
                        $sourceOptions = $data['source_options'] ?? [];
                        $sourceFilter = $data['filters']['source'] ?? 'all';
                        $monthFilter = $data['filters']['month'] ?? ($selectedMonth ?? now()->format('Y-m'));
                    @endphp
                    <form method="GET" action="{{ route('reports.show', 'expense') }}" class="card mb-3 no-print">
                        <div class="card-body">
                            <div class="row g-2 align-items-end">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small mb-0">Month</label>
                                    <input type="month" name="month" class="form-control form-control-sm" value="{{ $monthFilter }}">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-0">From</label>
                                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-0">To</label>
                                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small mb-0">Source</label>
                                    <select name="source" class="form-select form-select-sm">
                                        @foreach($sourceOptions as $key => $label)
                                            <option value="{{ $key }}" @selected($sourceFilter === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @php
                                    $now = \Carbon\Carbon::now();
                                    $quickMonths = [
                                        ['label' => 'This month', 'm' => $now->format('Y-m')],
                                        ['label' => 'Last month', 'm' => $now->copy()->subMonth()->format('Y-m')],
                                        ['label' => '2 months ago', 'm' => $now->copy()->subMonths(2)->format('Y-m')],
                                    ];
                                @endphp
                                @foreach($quickMonths as $qm)
                                    <a href="{{ route('reports.show', ['expense', 'month' => $qm['m'], 'source' => $sourceFilter]) }}"
                                       class="btn btn-outline-secondary btn-sm {{ $monthFilter === $qm['m'] ? 'active' : '' }}">{{ $qm['label'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    </form>

                    <p class="text-muted small mb-3">
                        Period: <strong>{{ \Carbon\Carbon::parse($from)->format('d M Y') }}</strong>
                        — <strong>{{ \Carbon\Carbon::parse($to)->format('d M Y') }}</strong>
                        @if($monthFilter)
                            · Month view: <strong>{{ \Carbon\Carbon::parse($monthFilter . '-01')->format('F Y') }}</strong>
                        @endif
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded h-100">
                                <strong>Total (period)</strong>
                                <div class="fs-4">Rs {{ number_format($data['total'] ?? 0, 2) }}</div>
                                <small class="text-muted">{{ $data['count'] ?? 0 }} lines</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-success bg-opacity-10 rounded h-100">
                                <strong>Linked to Accounting</strong>
                                <div class="fs-4 text-success">{{ $data['accounting_linked_count'] ?? 0 }}</div>
                                <small class="text-muted">Journal entries posted</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded h-100">
                                <strong>By source</strong>
                                @foreach($data['source_totals'] ?? [] as $st)
                                    <div class="d-flex justify-content-between small mt-1">
                                        <span>{{ $st['label'] }}</span>
                                        <span>Rs {{ number_format($st['total'], 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    @if(count($monthlyTotals) > 0)
                        <div class="card mb-4">
                            <div class="card-header py-2"><strong>Monthly expenses summary</strong></div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Month</th>
                                            <th class="text-end">Lines</th>
                                            <th class="text-end">Total (Rs)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($monthlyTotals as $mt)
                                            <tr class="{{ ($monthFilter && $mt['month'] === $monthFilter) ? 'table-primary' : '' }}">
                                                <td>
                                                    <a href="{{ route('reports.show', ['expense', 'month' => $mt['month'], 'source' => $sourceFilter]) }}" class="text-decoration-none">
                                                        {{ $mt['label'] }}
                                                    </a>
                                                </td>
                                                <td class="text-end">{{ $mt['count'] }}</td>
                                                <td class="text-end fw-semibold">Rs {{ number_format($mt['total'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @forelse($ledgerByMonth as $monthGroup)
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                <strong>{{ $monthGroup['label'] }}</strong>
                                <span class="badge bg-primary">Rs {{ number_format($monthGroup['total'], 2) }} · {{ $monthGroup['count'] }} lines</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Source</th>
                                            <th>Category</th>
                                            <th>Description</th>
                                            <th>Reference</th>
                                            <th>Person</th>
                                            <th class="text-end">Amount (Rs)</th>
                                            <th>Accounting</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($monthGroup['lines'] as $line)
                                            <tr>
                                                <td class="text-nowrap">{{ $line['date_label'] ?? $line['date'] }}</td>
                                                <td class="small">{{ $line['source'] }}</td>
                                                <td>{{ $line['category'] }}</td>
                                                <td class="small">{{ Str::limit($line['description'] ?? '', 60) }}</td>
                                                <td class="small">{{ $line['reference'] ?? '—' }}</td>
                                                <td class="small">{{ $line['person'] ?? '—' }}</td>
                                                <td class="text-end fw-semibold {{ ($line['amount'] ?? 0) < 0 ? 'text-danger' : '' }}">
                                                    {{ number_format($line['amount'], 2) }}
                                                </td>
                                                <td class="small">
                                                    @if(!empty($line['accounting_linked']))
                                                        <span class="badge bg-success">Posted</span>
                                                    @else
                                                        <span class="badge bg-secondary">Pending</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="6" class="text-end">{{ $monthGroup['label'] }} total</th>
                                            <th class="text-end">Rs {{ number_format($monthGroup['total'], 2) }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No expenses in date range.</p>
                    @endforelse

                    <p class="small text-muted mb-0">Expenses grouped by month with each line date. Use the month picker or quick buttons for this month / last month.</p>
                    @break

                @default
                    <p class="text-muted">This report is available with Export. Data view can be added here.</p>
            @endswitch
        </div>
    </div>
</div>
@endsection
