@extends('erp.layout')

@section('title', 'Inventory Report')

@section('css')
<style>
    .report-inv-page { width: 100%; max-width: 100%; margin: 0; padding: 0; }
    .report-inv-page .rpt-hero {
        background: linear-gradient(135deg, #0f4c3a 0%, #1a7a5e 100%);
        color: #fff;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
    }
    .report-inv-page .rpt-kpi {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
        height: 100%;
    }
    .report-inv-page .rpt-kpi .kpi-val { font-size: 1.25rem; font-weight: 700; line-height: 1.2; }
    .report-inv-page .rpt-kpi .kpi-lbl {
        font-size: .65rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        margin-top: .25rem;
    }
    .report-inv-page .table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .report-inv-page .tbl-inv {
        width: 100%;
        min-width: 960px;
        font-size: .82rem;
        margin: 0;
    }
    .report-inv-page .tbl-inv thead th {
        background: #f1f5f9;
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #475569;
        white-space: nowrap;
        padding: .6rem .5rem;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .report-inv-page .tbl-inv tbody td {
        padding: .55rem .5rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }
    .report-inv-page .tbl-inv tbody tr:hover { background: #f8fafc; }
    .report-inv-page .type-fabric { background: #dbeafe; color: #1d4ed8; font-size: .65rem; font-weight: 600; padding: 2px 8px; border-radius: 4px; }
    .report-inv-page .type-accessory { background: #e0f2fe; color: #0369a1; font-size: .65rem; font-weight: 600; padding: 2px 8px; border-radius: 4px; }
    .report-inv-page .st-ok { background: #dcfce7; color: #166534; font-size: .65rem; font-weight: 600; padding: 2px 8px; border-radius: 4px; }
    .report-inv-page .st-low { background: #fef3c7; color: #b45309; font-size: .65rem; font-weight: 600; padding: 2px 8px; border-radius: 4px; }
    .report-inv-page .st-out { background: #fee2e2; color: #b91c1c; font-size: .65rem; font-weight: 600; padding: 2px 8px; border-radius: 4px; }
    .report-inv-page .btn-print {
        background: #fff;
        color: #0f4c3a;
        font-weight: 600;
        border: none;
        padding: .45rem 1rem;
        border-radius: 8px;
    }
    .report-inv-page .btn-print:hover { background: #ecfdf5; }

    @media print {
        @page { size: landscape; margin: 8mm; }
        body { background: #fff !important; overflow: visible !important; }
        .sidebar, .erp-mobile-header, .erp-sidebar-backdrop, .no-print { display: none !important; }
        .erp-wrapper { display: block !important; }
        .content { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .report-inv-page .rpt-hero {
            background: #0f4c3a !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            border-radius: 0;
        }
        .report-inv-page .table-wrap { overflow: visible !important; }
        .report-inv-page .tbl-inv { min-width: 0; font-size: 9pt; }
        .report-inv-page .tbl-inv thead th { background: #eee !important; }
        .report-inv-page .card { border: none !important; box-shadow: none !important; }
    }
</style>
@endsection

@section('content')
<div class="report-inv-page">
    <div class="rpt-hero d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-boxes me-2"></i>Inventory Report</h4>
            <p class="mb-0 mt-1 opacity-75 small">
                Raw materials stock — fabric &amp; accessories · Period: {{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
            </p>
        </div>
        <div class="no-print d-flex gap-2 flex-wrap">
            <button type="button" onclick="window.print()" class="btn btn-print">
                <i class="bi bi-printer me-1"></i>Print
            </button>
            <a href="{{ route('reports.export', ['report' => 'inventory', 'format' => 'csv', 'from' => $from, 'to' => $to, 'type' => $type, 'stock_filter' => $stockFilter, 'q' => $search, 'sort' => $sort]) }}"
               class="btn btn-outline-light btn-sm">
                <i class="bi bi-download me-1"></i>CSV
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="card mb-3 no-print border-0 shadow-sm">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('reports.show', 'inventory') }}" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Search</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ $search }}" placeholder="Item name...">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All</option>
                        <option value="fabric" {{ $type === 'fabric' ? 'selected' : '' }}>Fabric</option>
                        <option value="accessory" {{ $type === 'accessory' ? 'selected' : '' }}>Accessory</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Stock</label>
                    <select name="stock_filter" class="form-select form-select-sm">
                        <option value="all" {{ $stockFilter === 'all' ? 'selected' : '' }}>All</option>
                        <option value="in_stock" {{ $stockFilter === 'in_stock' ? 'selected' : '' }}>In stock</option>
                        <option value="low" {{ $stockFilter === 'low' ? 'selected' : '' }}>Low (≤{{ $lowStockThreshold }})</option>
                        <option value="zero" {{ $stockFilter === 'zero' ? 'selected' : '' }}>Out of stock</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Sort</label>
                    <select name="sort" class="form-select form-select-sm">
                        <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Name</option>
                        <option value="qty_desc" {{ $sort === 'qty_desc' ? 'selected' : '' }}>Qty high → low</option>
                        <option value="qty_asc" {{ $sort === 'qty_asc' ? 'selected' : '' }}>Qty low → high</option>
                        <option value="value_desc" {{ $sort === 'value_desc' ? 'selected' : '' }}>Value high → low</option>
                        <option value="type" {{ $sort === 'type' ? 'selected' : '' }}>Type</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-search me-1"></i>Apply</button>
                    <a href="{{ route('reports.show', 'inventory') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-lg-2">
            <div class="card rpt-kpi text-center p-2">
                <div class="kpi-val text-success">{{ number_format($summary['total_items']) }}</div>
                <div class="kpi-lbl">Items</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card rpt-kpi text-center p-2">
                <div class="kpi-val text-primary">{{ number_format($summary['total_qty'], 2) }}</div>
                <div class="kpi-lbl">Total Qty</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card rpt-kpi text-center p-2">
                <div class="kpi-val text-warning">Rs {{ number_format($summary['stock_value'], 0) }}</div>
                <div class="kpi-lbl">Stock Value</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card rpt-kpi text-center p-2">
                <div class="kpi-val text-danger">{{ $summary['low_stock_count'] }}</div>
                <div class="kpi-lbl">Low Stock</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card rpt-kpi text-center p-2">
                <div class="kpi-val" style="color:#b91c1c">{{ $summary['out_of_stock_count'] }}</div>
                <div class="kpi-lbl">Out of Stock</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card rpt-kpi text-center p-2">
                <div class="kpi-val small text-muted">
                    <span class="text-success">+{{ number_format($summary['period_in'], 1) }}</span>
                    /
                    <span class="text-danger">-{{ number_format($summary['period_out'], 1) }}</span>
                </div>
                <div class="kpi-lbl">In / Out (period)</div>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-3 no-print">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-2 px-3 small">
                <strong>Fabric:</strong> {{ $summary['fabric_count'] }} items · Rs {{ number_format($summary['fabric_value'], 0) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-2 px-3 small">
                <strong>Accessories:</strong> {{ $summary['accessory_count'] }} items · Rs {{ number_format($summary['accessory_value'], 0) }}
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-wrap">
                <table class="table tbl-inv align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Unit</th>
                            <th class="text-end">Current Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Stock Value</th>
                            <th>Status</th>
                            <th class="text-end">Period In</th>
                            <th class="text-end">Period Out</th>
                            <th class="text-end">Net</th>
                            <th>Last Movement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr class="{{ $row['status'] === 'out' ? 'text-muted' : '' }}">
                            <td class="text-muted small">{{ $row['id'] }}</td>
                            <td class="fw-semibold" style="max-width:200px">
                                <div class="text-truncate" title="{{ $row['name'] }}">{{ $row['name'] }}</div>
                            </td>
                            <td>
                                <span class="{{ $row['type'] === 'fabric' ? 'type-fabric' : 'type-accessory' }}">
                                    {{ $row['type_label'] }}
                                </span>
                            </td>
                            <td class="text-nowrap">{{ $row['unit'] }}</td>
                            <td class="text-end fw-bold {{ $row['quantity'] > 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($row['quantity'], 2) }}
                            </td>
                            <td class="text-end text-nowrap small">
                                {{ $row['unit_price'] > 0 ? 'Rs '.number_format($row['unit_price'], 2) : '—' }}
                            </td>
                            <td class="text-end text-nowrap fw-semibold">
                                {{ $row['line_value'] > 0 ? 'Rs '.number_format($row['line_value'], 2) : '—' }}
                            </td>
                            <td>
                                @if($row['status'] === 'ok')
                                    <span class="st-ok">OK</span>
                                @elseif($row['status'] === 'low')
                                    <span class="st-low">Low</span>
                                @else
                                    <span class="st-out">Out</span>
                                @endif
                            </td>
                            <td class="text-end text-success small">{{ $row['qty_in'] > 0 ? number_format($row['qty_in'], 2) : '—' }}</td>
                            <td class="text-end text-danger small">{{ $row['qty_out'] > 0 ? number_format($row['qty_out'], 2) : '—' }}</td>
                            <td class="text-end small fw-semibold {{ $row['net_qty'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $row['net_qty'] != 0 ? number_format($row['net_qty'], 2) : '—' }}
                            </td>
                            <td class="small text-muted text-nowrap">
                                @if($row['last_movement_at'])
                                    {{ \Carbon\Carbon::parse($row['last_movement_at'])->format('d M Y') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-5">No inventory items match your filters.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">Totals:</td>
                            <td class="text-end text-success">{{ number_format($summary['total_qty'], 2) }}</td>
                            <td></td>
                            <td class="text-end text-warning">Rs {{ number_format($summary['stock_value'], 2) }}</td>
                            <td></td>
                            <td class="text-end text-success">{{ number_format($summary['period_in'], 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($summary['period_out'], 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <p class="small text-muted mt-2 mb-0 no-print">
        <strong>Period In/Out</strong> = stock movements between selected dates (purchases, issues, adjustments).
        <strong>Low stock</strong> = quantity ≤ {{ $lowStockThreshold }}.
    </p>
</div>
@endsection
