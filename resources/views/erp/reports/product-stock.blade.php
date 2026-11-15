@extends('erp.layout')

@section('title', 'Product Stock Report')

@section('css')
<style>
    /* Full-width report — no sidebar squeeze / crop */
    .report-stock-page { width: 100%; max-width: 100%; margin: 0; padding: 0; }
    .report-stock-page .rpt-hero {
        background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%);
        color: #fff;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
    }
    .report-stock-page .rpt-kpi {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
        height: 100%;
    }
    .report-stock-page .rpt-kpi .kpi-val { font-size: 1.4rem; font-weight: 700; line-height: 1.2; }
    .report-stock-page .rpt-kpi .kpi-lbl {
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        margin-top: .25rem;
    }
    .report-stock-page .table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .report-stock-page .tbl-stock {
        width: 100%;
        min-width: 900px;
        font-size: .82rem;
        margin: 0;
    }
    .report-stock-page .tbl-stock thead th {
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
    .report-stock-page .tbl-stock tbody td {
        padding: .55rem .5rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }
    .report-stock-page .tbl-stock tbody tr:hover { background: #f8fafc; }
    .report-stock-page .size-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
        max-width: 220px;
    }
    .report-stock-page .size-chip {
        display: inline-block;
        padding: 1px 6px;
        border-radius: 4px;
        font-size: .68rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .report-stock-page .size-chip.in-stock { background: #dcfce7; color: #166534; }
    .report-stock-page .size-chip.zero { background: #f1f5f9; color: #94a3b8; }
    .report-stock-page .cost-src {
        font-size: .65rem;
        padding: 1px 6px;
        border-radius: 4px;
        font-weight: 600;
    }
    .report-stock-page .cost-src-manual { background: #dbeafe; color: #1d4ed8; }
    .report-stock-page .cost-src-recipe { background: #fef3c7; color: #b45309; }
    .report-stock-page .cost-src-variation { background: #ede9fe; color: #6d28d9; }
    .report-stock-page .btn-print {
        background: #fff;
        color: #1e3a5f;
        font-weight: 600;
        border: none;
        padding: .45rem 1rem;
        border-radius: 8px;
    }
    .report-stock-page .btn-print:hover { background: #f0f7ff; }

    @media (max-width: 991px) {
        .content { padding: 12px !important; max-width: 100% !important; }
    }

    @media print {
        @page { size: landscape; margin: 8mm; }
        body { background: #fff !important; overflow: visible !important; }
        .sidebar, .erp-mobile-header, .erp-sidebar-backdrop, .no-print { display: none !important; }
        .erp-wrapper { display: block !important; }
        .content {
            max-width: 100% !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .report-stock-page .rpt-hero {
            background: #1e3a5f !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            border-radius: 0;
            margin-bottom: 8px;
        }
        .report-stock-page .table-wrap { overflow: visible !important; }
        .report-stock-page .tbl-stock { min-width: 0; font-size: 9pt; }
        .report-stock-page .tbl-stock thead th { background: #eee !important; }
        .report-stock-page .card { border: none !important; box-shadow: none !important; }
    }
</style>
@endsection

@section('content')
<div class="report-stock-page">
    <div class="rpt-hero d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2"></i>Product Stock Report</h4>
            <p class="mb-0 mt-1 opacity-75 small">Product-wise stock · Manual / Recipe / Variation cost · {{ now()->format('d M Y, H:i') }}</p>
        </div>
        <div class="no-print d-flex gap-2 flex-wrap">
            <button type="button" onclick="window.print()" class="btn btn-print">
                <i class="bi bi-printer me-1"></i>Print Report
            </button>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3 no-print border-0 shadow-sm">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('reports.show', 'product-stock') }}" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Search (SKU / Name)</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ $search }}" placeholder="Design No...">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Type</label>
                    <select name="product_type" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="simple" {{ $productType === 'simple' ? 'selected' : '' }}>Simple</option>
                        <option value="variable" {{ $productType === 'variable' ? 'selected' : '' }}>Sizes</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Zero stock</label>
                    <select name="show_zero" class="form-select form-select-sm">
                        <option value="0" {{ !$showZero ? 'selected' : '' }}>Hide</option>
                        <option value="1" {{ $showZero ? 'selected' : '' }}>Show</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-search me-1"></i>Filter</button>
                    <a href="{{ route('reports.show', 'product-stock') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="card rpt-kpi text-center p-2 p-md-3">
                <div class="kpi-val text-primary">{{ number_format($summary['total_products']) }}</div>
                <div class="kpi-lbl">Products</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card rpt-kpi text-center p-2 p-md-3">
                <div class="kpi-val text-success">{{ number_format($summary['total_stock_qty'], 0) }}</div>
                <div class="kpi-lbl">Total Units</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card rpt-kpi text-center p-2 p-md-3">
                <div class="kpi-val text-warning">Rs {{ number_format($summary['stock_value'], 0) }}</div>
                <div class="kpi-lbl">Stock Value</div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-wrap">
                <table class="table tbl-stock align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Design No</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th class="text-end">Stock</th>
                            <th>Sizes</th>
                            <th class="text-end">Unit Cost</th>
                            <th>Source</th>
                            <th class="text-end">Line Value</th>
                            <th class="text-end">Wholesale</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr class="{{ $row['total_stock'] <= 0 ? 'text-muted' : '' }}">
                            <td class="fw-bold text-nowrap {{ $row['total_stock'] > 0 ? 'text-primary' : '' }}">
                                {{ $row['sku'] ?: '—' }}
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width:160px" title="{{ $row['name'] }}">{{ $row['name'] }}</div>
                            </td>
                            <td class="small text-muted text-nowrap">{{ $row['category'] }}</td>
                            <td class="text-nowrap">
                                @if($row['product_type'] === 'variable')
                                    <span class="badge bg-primary-subtle text-primary border" style="font-size:.65rem">Sizes</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border" style="font-size:.65rem">Simple</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-nowrap {{ $row['total_stock'] > 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($row['total_stock'], 0) }}
                            </td>
                            <td>
                                @if($row['product_type'] === 'variable' && count($row['variations']) > 0)
                                    <div class="size-grid">
                                        @foreach($row['variations'] as $v)
                                            <span class="size-chip {{ $v['stock'] > 0 ? 'in-stock' : 'zero' }}">
                                                {{ $v['name'] }}:{{ number_format($v['stock'], 0) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap small">
                                @if($row['cost_price'] > 0)
                                    Rs {{ number_format($row['cost_price'], 2) }}
                                @else
                                    <span class="text-danger">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if($row['cost_source'] === 'Manual')
                                    <span class="cost-src cost-src-manual">Manual</span>
                                @elseif($row['cost_source'] === 'Recipe')
                                    <span class="cost-src cost-src-recipe">Recipe</span>
                                @elseif($row['cost_source'] === 'Variation')
                                    <span class="cost-src cost-src-variation">Variation</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap small fw-semibold">
                                @if($row['line_value'] > 0)
                                    Rs {{ number_format($row['line_value'], 0) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end text-nowrap small">
                                {{ $row['wholesale_price'] > 0 ? 'Rs '.number_format($row['wholesale_price'], 2) : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">No products found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">Totals:</td>
                            <td class="text-end text-success">{{ number_format($summary['total_stock_qty'], 0) }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-end text-warning">Rs {{ number_format($summary['stock_value'], 0) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <p class="small text-muted mt-2 mb-0 no-print">
        <strong>Cost source:</strong>
        <span class="cost-src cost-src-manual">Manual</span> = product cost price ·
        <span class="cost-src cost-src-recipe">Recipe</span> = manufacturing recipe cost ·
        <span class="cost-src cost-src-variation">Variation</span> = size-wise purchase price
    </p>
</div>
@endsection
