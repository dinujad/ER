@extends('erp.layout')

@section('content')
@php
    $stats = $data['stats'] ?? [];
    $rec = $data['recommendation'] ?? [];
    $sales = $data['sales_history'] ?? [];
    $variations = $data['variations'] ?? [];
    $buyers = $data['buyers'] ?? [];
    $orderLines = $data['order_lines'] ?? [];
@endphp
<div class="container-fluid py-4">
    <div class="mb-3">
        <a href="{{ route('reports.show', array_merge(['report' => 'trending-products'], request()->only('from', 'to', 'channel'))) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Trending Products
        </a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $product->name }}</h4>
            <p class="text-muted small mb-0">
                SKU {{ $product->sku ?? '—' }}
                · Rank #{{ $stats['rank'] ?? '—' }}
                · {{ $data['filters']['channel_label'] ?? 'All' }}
                · {{ $from }} to {{ $to }}
            </p>
        </div>
        @canRoute('products.edit')
<a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-outline-primary">Edit product</a>
@endcanRoute
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100 border-primary"><div class="card-body text-center">
                <div class="small text-muted">Qty sold</div>
                <div class="fs-4 fw-bold">{{ number_format($stats['total_qty'] ?? 0, 0) }}</div>
                <small class="text-muted d-block">POS {{ number_format($stats['retail_qty'] ?? 0, 0) }} · Wholesale {{ number_format($stats['wholesale_qty'] ?? 0, 0) }}</small>
                <small class="{{ ($stats['growth_percent'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ ($stats['growth_percent'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($stats['growth_percent'] ?? 0, 1) }}% vs prev
                </small>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <div class="small text-muted">Total sales</div>
                <div class="fs-4 fw-bold">Rs {{ number_format($stats['total_sales'] ?? 0, 2) }}</div>
                <small>{{ $stats['order_count'] ?? 0 }} orders</small>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <div class="small text-muted">Avg sell price</div>
                <div class="fs-4 fw-bold">Rs {{ number_format($stats['avg_unit_price'] ?? 0, 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-info"><div class="card-body text-center">
                <div class="small text-muted">Trend</div>
                <div class="fs-5 fw-bold">{{ $rec['trend_label'] ?? '—' }}</div>
                @if(($rec['suggested_increase_percent'] ?? 0) > 0)
                    <small class="text-success">Suggest +{{ $rec['suggested_increase_percent'] }}%</small>
                @endif
            </div></div>
        </div>
    </div>

    <div class="card border-info mb-4">
        <div class="card-header bg-info bg-opacity-10 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <strong><i class="bi bi-robot me-1"></i> {{ $rec['agent_brand'] ?? 'E media AI agent recommendation' }}</strong>
            <span class="badge {{ ($rec['source'] ?? '') === 'ai+rules' ? 'bg-primary' : 'bg-secondary' }}">
                {{ ($rec['source'] ?? '') === 'ai+rules' ? 'AI + Logic' : 'Logic-based' }}
            </span>
        </div>
        <div class="card-body">
            <p class="fw-semibold mb-2">{{ $rec['logic_summary'] ?? '' }}</p>
            @foreach($rec['insights'] ?? [] as $ins)
                <div class="alert alert-{{ $ins['level'] === 'success' ? 'success' : ($ins['level'] === 'warning' ? 'warning' : ($ins['level'] === 'primary' ? 'primary' : 'light')) }} py-2 px-3 small mb-2">
                    <strong>{{ $ins['title'] }}</strong><br>{{ $ins['body'] }}
                </div>
            @endforeach
            <div class="border rounded p-3 bg-light mt-3">
                <div class="fw-semibold mb-2 text-primary">E media AI agent recommendation</div>
                <div class="small" style="white-space: pre-wrap;">{{ $rec['recommendation'] ?? '' }}</div>
            </div>
            @if(($rec['suggested_increase_percent'] ?? 0) > 0 && ($rec['suggested_wholesale_price'] ?? 0) > 0)
                <div class="mt-3 p-3 border border-success rounded bg-success bg-opacity-10">
                    <strong>Price action:</strong>
                    Wholesale Rs {{ number_format($rec['current_avg_price'] ?? 0, 2) }}
                    → <strong>Rs {{ number_format($rec['suggested_wholesale_price'], 2) }}</strong>
                    (+{{ $rec['suggested_increase_percent'] }}%) — trend නිසා increase try කරන්න පුළුවන්.
                </div>
            @endif
            @if(($rec['source'] ?? '') !== 'ai+rules')
                <p class="text-muted small mb-0 mt-2">Add <code>GEMINI_API_KEY</code> to .env for full AI narrative from E media agent.</p>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><strong>Sales history (monthly)</strong></div>
                <div class="card-body">
                    @if(!empty($sales['labels']))
                        <canvas id="trendSalesChart" height="120"></canvas>
                    @else
                        <p class="text-muted small mb-0">No monthly history yet.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><strong>Top buyers</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Customer</th><th class="text-end">Orders</th><th class="text-end">Qty</th><th class="text-end">Rs</th></tr></thead>
                        <tbody>
                            @forelse($buyers as $b)
                            <tr>
                                <td>
                                    {{ $b['customer_name'] }}
                                    @if($b['customer_phone'])<br><small class="text-muted">{{ $b['customer_phone'] }}</small>@endif
                                </td>
                                <td class="text-end">{{ $b['order_count'] }}</td>
                                <td class="text-end">{{ number_format($b['total_qty'], 0) }}</td>
                                <td class="text-end">{{ number_format($b['total_sales'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">No buyers in period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($variations))
    <div class="card mb-4">
        <div class="card-header"><strong>Variation sales</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Variation</th><th>SKU</th><th class="text-end">Qty</th><th class="text-end">POS Retail</th><th class="text-end">Wholesale</th><th class="text-end">Avg price</th><th class="text-end">Sales</th></tr>
                </thead>
                <tbody>
                    @foreach($variations as $v)
                    <tr>
                        <td class="fw-semibold">{{ $v['variation_name'] }}</td>
                        <td class="small text-muted">{{ $v['sub_sku'] ?: '—' }}</td>
                        <td class="text-end">{{ number_format($v['total_qty'], 0) }}</td>
                        <td class="text-end">{{ number_format($v['retail_qty'] ?? 0, 0) }}</td>
                        <td class="text-end">{{ number_format($v['wholesale_qty'] ?? 0, 0) }}</td>
                        <td class="text-end">{{ number_format($v['avg_price'], 2) }}</td>
                        <td class="text-end">{{ number_format($v['total_sales'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><strong>Who bought — order lines</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Order</th>
                            <th>Channel</th>
                            <th>Customer</th>
                            <th>Sales person</th>
                            <th>Variation</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orderLines as $line)
                        <tr>
                            <td>{{ $line['order_date'] }}</td>
                            <td>
                                @if($line['order_id'])
                                    <a href="{{ route('orders.show', $line['order_id']) }}">{{ $line['order_number'] }}</a>
                                @else
                                    {{ $line['order_number'] }}
                                @endif
                            </td>
                            <td>{{ $line['channel'] ?? '—' }}</td>
                            <td>
                                {{ $line['customer_name'] }}
                                @if($line['customer_phone'])<br><small class="text-muted">{{ $line['customer_phone'] }}</small>@endif
                            </td>
                            <td>{{ $line['sales_person'] ?? '—' }}</td>
                            <td>{{ $line['variation_name'] }}</td>
                            <td class="text-end">{{ number_format($line['quantity'], 0) }}</td>
                            <td class="text-end">{{ number_format($line['unit_price'], 2) }}</td>
                            <td class="text-end">{{ number_format($line['line_total'], 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-muted text-center py-3">No order lines in period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if(!empty($sales['labels']))
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('trendSalesChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($sales['labels']),
            datasets: [
                {
                    label: 'Qty sold',
                    data: @json($sales['quantities']),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    yAxisID: 'y',
                },
                {
                    label: 'Avg price (Rs)',
                    data: @json($sales['avg_prices']),
                    type: 'line',
                    borderColor: '#22c55e',
                    backgroundColor: 'transparent',
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Qty' } },
                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Rs' } }
            }
        }
    });
});
</script>
@endif
@endsection
