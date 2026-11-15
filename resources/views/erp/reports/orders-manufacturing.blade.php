@extends('erp.layout')

@section('content')
@php
    $orders = $data['orders'] ?? [];
    $sum = $data['summary'] ?? [];
    $filters = $data['filters'] ?? [];
    $salesPersons = $data['sales_persons'] ?? collect();
@endphp
<style>
    .rpt-hero { background:linear-gradient(135deg,#7c2d12 0%,#ea580c 100%); color:#fff; border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:1.5rem; }
    .mfg-needed { background:#fff7ed !important; }
    @media print { .no-print { display:none!important; } }
</style>
<div class="container-fluid py-3">
    <div class="rpt-hero d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-gear-wide-connected me-2"></i>Orders — Manufacturing Report</h4>
            <p class="mb-0 mt-1 opacity-75 small">
                Order number + stock check → manufacture කරන්න ඕනද කියලා full list
                · {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
            </p>
        </div>
        <div class="no-print d-flex gap-2 flex-wrap">
            <a href="{{ route('reports.export', array_merge(request()->query(), ['report' => 'orders-manufacturing', 'format' => 'csv'])) }}" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
            </a>
            <button type="button" onclick="window.print()" class="btn btn-light btn-sm"><i class="bi bi-printer me-1"></i>Print</button>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Reports</a>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.show', 'orders-manufacturing') }}" class="card mb-4 no-print">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Manufacturing</label>
                    <select name="manufacturing" class="form-select form-select-sm">
                        <option value="all" @selected(($filters['manufacturing'] ?? 'all') === 'all')>All</option>
                        <option value="needed" @selected(($filters['manufacturing'] ?? '') === 'needed')>අවශ්‍යයි only</option>
                        <option value="not_needed" @selected(($filters['manufacturing'] ?? '') === 'not_needed')>Stock OK only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All open</option>
                        @foreach(['pending' => 'Pending', 'processing' => 'Processing', 'afterchecking' => 'After Checking', 'draft' => 'Draft'] as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Sales person</label>
                    <select name="sales_person_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($salesPersons as $sp)
                            <option value="{{ $sp->id }}" @selected((string)($filters['sales_person_id'] ?? '') === (string)$sp->id)>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Order # search</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ $filters['search'] ?? '' }}" placeholder="ORD-...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <div class="small text-muted">Open orders</div>
                <div class="fs-4 fw-bold">{{ $sum['order_count'] ?? 0 }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning h-100"><div class="card-body text-center">
                <div class="small text-muted">Manufacturing අවශ්‍ය</div>
                <div class="fs-4 fw-bold text-warning">{{ $sum['needs_manufacturing_count'] ?? 0 }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-success h-100"><div class="card-body text-center">
                <div class="small text-muted">Stock OK</div>
                <div class="fs-4 fw-bold text-success">{{ $sum['stock_ok_count'] ?? 0 }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body text-center">
                <div class="small text-muted">Total pcs to manufacture</div>
                <div class="fs-4 fw-bold">{{ number_format($sum['total_shortage_pcs'] ?? 0, 0) }}</div>
            </div></div>
        </div>
    </div>

    @forelse($orders as $order)
    <div class="card mb-3 {{ $order['needs_manufacturing'] ? 'border-warning mfg-needed' : '' }}">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
            <div>
                <a href="{{ route('orders.show', $order['order_id']) }}" class="fw-bold text-decoration-none">{{ $order['order_number'] }}</a>
                <span class="text-muted small ms-2">{{ $order['order_date'] }}</span>
                <span class="badge bg-secondary ms-1">{{ $order['status_label'] }}</span>
                @if($order['needs_manufacturing'])
                    <span class="badge bg-warning text-dark ms-1">Manufacturing අවශ්‍ය</span>
                @else
                    <span class="badge bg-success ms-1">Stock OK</span>
                @endif
            </div>
            <div class="small text-end">
                <div>{{ $order['customer'] }}</div>
                <div class="text-muted">{{ $order['sales_person'] }} · Rs {{ number_format($order['total_amount'], 2) }}</div>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Variation</th>
                        <th class="text-end">Order qty</th>
                        <th class="text-end">Stock</th>
                        <th class="text-end">Make qty</th>
                        <th>Manufacturing</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order['lines'] as $line)
                    <tr class="{{ $line['needs_manufacturing'] ? 'table-warning' : '' }}">
                        <td>{{ $line['product_name'] }} <small class="text-muted">{{ $line['product_sku'] }}</small></td>
                        <td>{{ $line['variation_name'] }}</td>
                        <td class="text-end">{{ number_format($line['ordered_qty'], 0) }}</td>
                        <td class="text-end">{{ number_format($line['available_stock'], 0) }}</td>
                        <td class="text-end fw-semibold {{ $line['shortage_qty'] > 0 ? 'text-danger' : '' }}">{{ number_format($line['shortage_qty'], 0) }}</td>
                        <td>
                            @if($line['needs_manufacturing'])
                                <span class="text-danger fw-semibold">{{ $line['manufacturing_label'] }}</span>
                            @else
                                <span class="text-success">{{ $line['manufacturing_label'] }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @if($order['total_shortage_qty'] > 0)
                <tfoot>
                    <tr class="table-light">
                        <th colspan="4" class="text-end">Order total to manufacture:</th>
                        <th class="text-end text-danger">{{ number_format($order['total_shortage_qty'], 0) }} pcs</th>
                        <th></th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @empty
    <div class="alert alert-light border">No open orders in this period / filter.</div>
    @endforelse
</div>
@endsection
