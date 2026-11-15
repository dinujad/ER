@extends('erp.layout')

@section('content')
@php
    $rows = $data['rows'] ?? [];
    $sum = $data['summary'] ?? [];
    $filters = $data['filters'] ?? [];
    $channel = $filters['channel'] ?? 'all';
@endphp
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
        <div>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left me-1"></i> Reports</a>
            <h4 class="mb-0 fw-bold">{{ $title }}</h4>
            <p class="text-muted small mb-0">POS retail and wholesale orders are listed separately. Pick a channel to see only that report.</p>
        </div>
        <div class="dropdown">
            <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">Export</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @php $exportParams = array_merge(request()->query(), ['report' => 'trending-products', 'from' => $from, 'to' => $to]); @endphp
                <li><a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'csv'])) }}"><i class="bi bi-file-earmark-spreadsheet me-2"></i> CSV</a></li>
            </ul>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.show', 'trending-products') }}" class="card mb-4 no-print">
        <div class="card-body d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label small mb-0">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}" required>
            </div>
            <div>
                <label class="form-label small mb-0">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}" required>
            </div>
            <div>
                <label class="form-label small mb-0">Channel</label>
                <select name="channel" class="form-select form-select-sm">
                    <option value="all" @selected($channel === 'all')>All</option>
                    <option value="retail" @selected($channel === 'retail')>POS Retail</option>
                    <option value="wholesale" @selected($channel === 'wholesale')>Wholesale orders</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        </div>
    </form>

    <p class="text-muted small">
        Period: <strong>{{ $from }}</strong> to <strong>{{ $to }}</strong>
        · <strong>{{ $filters['channel_label'] ?? 'All' }}</strong>
        · Compared to <strong>{{ $filters['prev_from'] ?? '—' }}</strong> – <strong>{{ $filters['prev_to'] ?? '—' }}</strong>
    </p>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary h-100"><div class="card-body text-center">
                <div class="small text-muted">Products sold</div>
                <div class="fs-4 fw-bold">{{ $sum['product_count'] ?? 0 }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <div class="small text-muted">POS Retail qty</div>
                <div class="fs-4 fw-bold">{{ number_format($sum['retail_qty'] ?? 0, 0) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <div class="small text-muted">Wholesale qty</div>
                <div class="fs-4 fw-bold">{{ number_format($sum['wholesale_qty'] ?? 0, 0) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <div class="small text-muted">Total sales</div>
                <div class="fs-4 fw-bold">Rs {{ number_format($sum['total_sales'] ?? 0, 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning h-100"><div class="card-body text-center">
                <div class="small text-muted">Trending</div>
                <div class="fs-4 fw-bold text-warning">{{ $sum['trending_count'] ?? 0 }}</div>
            </div></div>
        </div>
    </div>

    @forelse($rows as $row)
        @php $p = $row['product']; @endphp
        <div class="card mb-3 {{ $row['is_trending'] ? 'border-warning' : '' }}">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <span class="badge bg-secondary me-1">#{{ $row['rank'] }}</span>
                    <strong>{{ $p->name }}</strong>
                    <span class="text-muted small">({{ $p->sku ?? '—' }})</span>
                    @if($row['is_trending'])
                        <span class="badge bg-warning text-dark ms-1"><i class="bi bi-graph-up-arrow"></i> Trending</span>
                    @endif
                    <div class="small text-muted mt-1">
                        {{ number_format($row['total_qty'], 0) }} pcs
                        · POS {{ number_format($row['retail_qty'] ?? 0, 0) }}
                        · Wholesale {{ number_format($row['wholesale_qty'] ?? 0, 0) }}
                        · Rs {{ number_format($row['total_sales'], 2) }}
                        · Growth
                        <span class="{{ $row['growth_percent'] >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                            {{ $row['growth_percent'] >= 0 ? '+' : '' }}{{ number_format($row['growth_percent'], 1) }}%
                        </span>
                        (prev {{ number_format($row['prev_qty'], 0) }} pcs)
                    </div>
                </div>
                <a href="{{ route('reports.trending-products.show', ['product' => $p->id, 'from' => $from, 'to' => $to, 'channel' => $channel]) }}"
                   class="btn btn-sm btn-primary">
                    <i class="bi bi-eye me-1"></i> View details &amp; AI
                </a>
            </div>
            @if(!empty($row['variations']))
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Variation</th>
                                <th>SKU</th>
                                <th class="text-end">Qty sold</th>
                                @if($channel === 'all')
                                    <th class="text-end">POS Retail</th>
                                    <th class="text-end">Wholesale</th>
                                @endif
                                <th class="text-end">Share</th>
                                <th class="text-end">Avg price</th>
                                <th class="text-end">Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($row['variations'] as $v)
                            <tr>
                                <td class="fw-semibold">{{ $v['variation_name'] }}</td>
                                <td class="small text-muted">{{ $v['sub_sku'] ?: '—' }}</td>
                                <td class="text-end">{{ number_format($v['total_qty'], 0) }}</td>
                                @if($channel === 'all')
                                    <td class="text-end">{{ number_format($v['retail_qty'] ?? 0, 0) }}</td>
                                    <td class="text-end">{{ number_format($v['wholesale_qty'] ?? 0, 0) }}</td>
                                @endif
                                <td class="text-end">{{ $v['share_percent'] }}%</td>
                                <td class="text-end">{{ number_format($v['avg_price'], 2) }}</td>
                                <td class="text-end">{{ number_format($v['total_sales'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    @empty
        <div class="alert alert-light border">No product sales in this period.</div>
    @endforelse
</div>
@endsection
