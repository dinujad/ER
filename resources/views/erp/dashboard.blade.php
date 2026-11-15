@extends('erp.layout')

@section('content')
<style>
    /* Scoped to dashboard only – do not affect layout/sidebar */
    .dashboard-page .dashboard-hero { background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); color: #fff; border-radius: 16px; padding: 1.75rem; margin-bottom: 1.5rem; box-shadow: 0 10px 40px rgba(30,58,95,0.2); }
    .dashboard-page .dashboard-hero h1 { font-weight: 700; font-size: 1.5rem; margin: 0; letter-spacing: -0.02em; }
    .dashboard-page .dashboard-hero .text-opacity-90 { opacity: 0.9; font-size: 0.9rem; }
    .dashboard-page .kpi-card { border: none; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); transition: transform 0.2s, box-shadow 0.2s; overflow: hidden; }
    .dashboard-page .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,0.1); }
    .dashboard-page .kpi-card .card-body { padding: 1.25rem 1.5rem; }
    .dashboard-page .kpi-card .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    .dashboard-page .kpi-card .kpi-value { font-size: 1.75rem; font-weight: 700; color: #1a1a2e; line-height: 1.2; }
    .dashboard-page .kpi-card .kpi-label { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 0.25rem; }
    .dashboard-page .section-card { border: none; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
    .dashboard-page .section-card .card-header { background: #fff; border-bottom: 1px solid #eee; padding: 1rem 1.5rem; font-weight: 600; font-size: 1rem; color: #1a1a2e; display: flex; align-items: center; justify-content: space-between; }
    .dashboard-page .section-card .card-header .bi { font-size: 1.1rem; color: #4e73df; margin-right: 0.5rem; }
    .dashboard-page .section-card .table { margin: 0; }
    .dashboard-page .section-card .table th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6c757d; font-weight: 600; border-bottom: 1px solid #eee; padding: 0.75rem 1rem; }
    .dashboard-page .section-card .table td { padding: 0.75rem 1rem; vertical-align: middle; }
    .dashboard-page .section-card .table tbody tr:hover { background-color: #f8fafc; }
    .dashboard-page .status-badge { font-size: 0.7rem; padding: 0.35em 0.65em; border-radius: 6px; font-weight: 600; text-transform: uppercase; }
    .dashboard-page .status-badge.progress { background: #e3f2fd; color: #1565c0; }
    .dashboard-page .status-badge.washing { background: #fff3e0; color: #e65100; }
    .dashboard-page .status-badge.packing { background: #e8f5e9; color: #2e7d32; }
    .dashboard-page .status-badge.cutting { background: #f3e5f5; color: #6a1b9a; }
    .dashboard-page .summary-row { display: flex; align-items: center; padding: 0.6rem 0; border-bottom: 1px solid #f0f0f0; }
    .dashboard-page .summary-row:last-child { border-bottom: none; }
    .dashboard-page .summary-row .label { color: #6c757d; font-size: 0.9rem; }
    .dashboard-page .summary-row .value { font-weight: 600; color: #1a1a2e; }
    .dashboard-page .alert-low { background: #fff8f0; border: 1px solid #ffcc80; color: #e65100; border-radius: 10px; }
    .dashboard-page .link-card { color: inherit; text-decoration: none; display: block; }
    .dashboard-page .link-card:hover { color: inherit; }
    .dashboard-page .chart-card { border: none; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); overflow: hidden; }
    .dashboard-page .chart-card .card-header { background: #fff; border-bottom: 1px solid #eee; font-weight: 600; padding: 1rem 1.25rem; }
    .dashboard-page .chart-container { position: relative; height: 280px; padding: 1rem; }
    .dashboard-page .prod-kpi { border-left: 4px solid #0ea5e9; }
    .dashboard-page .line-indent td:first-child { padding-left: 2rem; }
    .dashboard-page .job-group-row { background: #f8fafc; }
</style>

<div class="dashboard-page">
<div class="dashboard-hero d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1>Dashboard</h1>
        <p class="text-opacity-90 mb-0 mt-1">Overview of ongoing jobs, inventory, and reports</p>
    </div>
    <div class="text-end text-opacity-90 small">
        {{ now()->format('l, F j, Y') }}
    </div>
</div>

{{-- Duration filter --}}
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <span class="text-muted small me-1">Period:</span>
    <a href="{{ route('erp.dashboard') }}?duration=today" class="btn btn-sm {{ ($duration ?? '') === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">Today</a>
    <a href="{{ route('erp.dashboard') }}?duration=yesterday" class="btn btn-sm {{ ($duration ?? '') === 'yesterday' ? 'btn-primary' : 'btn-outline-secondary' }}">Yesterday</a>
    <a href="{{ route('erp.dashboard') }}?duration=week" class="btn btn-sm {{ ($duration ?? '') === 'week' ? 'btn-primary' : 'btn-outline-secondary' }}">Week</a>
    <a href="{{ route('erp.dashboard') }}?duration=month" class="btn btn-sm {{ ($duration ?? '') === 'month' ? 'btn-primary' : 'btn-outline-secondary' }}">Month</a>
    <a href="{{ route('erp.dashboard') }}?duration=year" class="btn btn-sm {{ ($duration ?? '') === 'year' ? 'btn-primary' : 'btn-outline-secondary' }}">Year</a>
    <span class="text-muted small ms-2">{{ $durationLabel ?? 'This Month' }}</span>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('jobs.index') }}" class="link-card">
            <div class="card kpi-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
                    <div>
                        <div class="kpi-value">{{ $ongoingJobsCount }}</div>
                        <div class="kpi-label">Ongoing Jobs ({{ $durationLabel ?? 'Period' }})</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('inventory.index') }}" class="link-card">
            <div class="card kpi-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <div class="kpi-value">{{ $materialsCount }}</div>
                        <div class="kpi-label">Raw Materials</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('inventory.index') }}" class="link-card">
            <div class="card kpi-card h-100 {{ $materialsLowStock > 0 ? 'border-warning' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon {{ $materialsLowStock > 0 ? 'bg-warning bg-opacity-10 text-warning' : 'bg-secondary bg-opacity-10 text-secondary' }}">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <div class="kpi-value">{{ $materialsLowStock }}</div>
                        <div class="kpi-label">Low Stock Items</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('reports.show', 'sale') }}" class="link-card">
            <div class="card kpi-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <div class="kpi-value">{{ number_format($salesTotal + $totalSales, 0) }}</div>
                        <div class="kpi-label">Sales ({{ $durationLabel ?? 'Period' }})</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    {{-- Purchase & Sales breakdown --}}
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('reports.show', 'purchase') }}" class="link-card">
            <div class="card kpi-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-cart-plus"></i>
                    </div>
                    <div>
                        <div class="kpi-value">{{ number_format($purchaseAmount ?? 0, 0) }}</div>
                        <div class="kpi-label">Purchase Amount</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('purchases.index') }}" class="link-card">
            <div class="card kpi-card h-100 {{ ($duePurchase ?? 0) > 0 ? 'border-danger' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon {{ ($duePurchase ?? 0) > 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-secondary bg-opacity-10 text-secondary' }}">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <div class="kpi-value">{{ number_format($duePurchase ?? 0, 0) }}</div>
                        <div class="kpi-label">Due Purchase</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('reports.show', 'sale') }}?customer_type=wholesale" class="link-card">
            <div class="card kpi-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-dark bg-opacity-10 text-dark">
                        <i class="bi bi-shop"></i>
                    </div>
                    <div>
                        <div class="kpi-value">{{ number_format($salesWholesale ?? 0, 0) }}</div>
                        <div class="kpi-label">Sales (Wholesale)</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('reports.show', 'sale') }}?customer_type=retail" class="link-card">
            <div class="card kpi-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-basket"></i>
                    </div>
                    <div>
                        <div class="kpi-value">{{ number_format($salesRetail ?? 0, 0) }}</div>
                        <div class="kpi-label">Sales (Retail)</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- Pie Charts --}}
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card chart-card h-100">
            <div class="card-header"><i class="bi bi-pie-chart-fill me-2 text-primary"></i> Sales by Type ({{ $durationLabel ?? 'Period' }})</div>
            <div class="card-body p-0">
                <div class="chart-container">
                    <canvas id="chartSalesByType" width="300" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card chart-card h-100">
            <div class="card-header"><i class="bi bi-graph-up-arrow me-2 text-success"></i> Purchase vs Sales ({{ $durationLabel ?? 'Period' }})</div>
            <div class="card-body p-0">
                <div class="chart-container">
                    <canvas id="chartPurchaseVsSales" width="300" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Ongoing Jobs --}}
    <div class="col-lg-8">
        <div class="card section-card">
            <div class="card-header">
                <span><i class="bi bi-list-task"></i> Ongoing Jobs</span>
                <a href="{{ route('jobs.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
            <div class="card-body p-0">
                @if($ongoingJobs->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mb-0 mt-2">No ongoing jobs</p>
                        <a href="{{ route('jobs.create') }}" class="btn btn-primary btn-sm mt-2">Create job</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Job #</th>
                                    <th>Status</th>
                                    <th>Product</th>
                                    <th class="text-end">Produced</th>
                                    <th>Issue date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ongoingJobs as $job)
                                <tr>
                                    <td><strong>{{ $job->job_number }}</strong></td>
                                    <td>
                                        @php
                                            $statusClass = 'progress';
                                            if (str_contains($job->status, 'CUTTING')) $statusClass = 'cutting';
                                            elseif (str_contains($job->status, 'WASH')) $statusClass = 'washing';
                                            elseif (str_contains($job->status, 'PACKING') || str_contains($job->status, 'LINE')) $statusClass = 'packing';
                                        @endphp
                                        <span class="status-badge {{ $statusClass }}">{{ str_replace('_', ' ', $job->status) }}</span>
                                    </td>
                                    <td>{{ $job->product?->name ?? '—' }}</td>
                                    <td class="text-end font-monospace">{{ number_format($job->totalLineHourlyQuantity()) }}</td>
                                    <td>{{ $job->issue_date?->format('M d, Y') ?? '—' }}</td>
                                    <td>
                                        <a href="{{ route('jobs.show', $job) }}" class="btn btn-sm btn-outline-secondary">Open</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Inventory & Report Summary --}}
    <div class="col-lg-4">
        <div class="card section-card mb-3">
            <div class="card-header">
                <span><i class="bi bi-box-seam"></i> Inventory Summary</span>
            </div>
            <div class="card-body">
                <div class="summary-row d-flex justify-content-between">
                    <span class="label">Products</span>
                    <span class="value">{{ $productsCount }}</span>
                </div>
                <div class="summary-row d-flex justify-content-between">
                    <span class="label">Materials (types)</span>
                    <span class="value">{{ $materialsCount }}</span>
                </div>
                <div class="summary-row d-flex justify-content-between">
                    <span class="label">Materials value</span>
                    <span class="value">{{ number_format($totalMaterialsValue, 2) }}</span>
                </div>
                @if($materialsLowStock > 0)
                    <div class="alert alert-low mt-2 mb-0 py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i> {{ $materialsLowStock }} item(s) at or below 10 units
                    </div>
                @endif
                <a href="{{ route('inventory.index') }}" class="btn btn-outline-primary btn-sm w-100 mt-3">Go to Inventory</a>
            </div>
        </div>

        <div class="card section-card">
            <div class="card-header">
                <span><i class="bi bi-graph-up"></i> Report Summary</span>
            </div>
            <div class="card-body">
                <div class="summary-row d-flex justify-content-between">
                    <span class="label">Orders this month</span>
                    <span class="value">{{ $ordersThisMonth }}</span>
                </div>
                <div class="summary-row d-flex justify-content-between">
                    <span class="label">Revenue (month)</span>
                    <span class="value">{{ number_format($ordersTotalAmount + $totalSales, 2) }}</span>
                </div>
                <div class="summary-row d-flex justify-content-between">
                    <span class="label">Pending orders</span>
                    <span class="value">{{ $ordersPending }}</span>
                </div>
                <div class="summary-row d-flex justify-content-between">
                    <span class="label">Finished goods (QC)</span>
                    <span class="value">{{ number_format($finishedGoods) }}</span>
                </div>
                <div class="summary-row d-flex justify-content-between">
                    <span class="label">Damaged (QC)</span>
                    <span class="value">{{ number_format($damagedGoods) }}</span>
                </div>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-primary btn-sm w-100 mt-3">View Reports</a>
            </div>
        </div>
    </div>
</div>

{{-- Quick links --}}
<div class="row mt-3">
    <div class="col-12">
        <div class="card section-card">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="text-muted small me-2">Quick actions:</span>
                    <a href="{{ route('jobs.create') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>New job</a>
                    <a href="{{ route('inventory.issue') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up me-1"></i>Issue stock</a>
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-cart me-1"></i>Orders</a>
                    <a href="{{ route('supervisor.dashboard') }}" class="btn btn-sm btn-outline-info"><i class="bi bi-person-badge me-1"></i>Supervisor</a>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $prod = $productionSummary ?? ['job_rows' => [], 'totals' => [], 'today_in_range' => true, 'today_label' => now()->format('d M Y')];
    $pt = $prod['totals'] ?? [];
@endphp

{{-- Line production summary (below charts & existing sections) --}}
<div class="card section-card mt-4 prod-kpi">
    <div class="card-header">
        <span><i class="bi bi-clock-history"></i> Line Production — Hourly Output &amp; Targets</span>
        <a href="{{ route('hourly-production.create') }}" class="btn btn-sm btn-outline-primary">Record hourly output</a>
    </div>
    <div class="card-body pb-2">
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="border rounded p-2 text-center h-100">
                    <div class="small text-muted">Jobs with lines</div>
                    <div class="fs-4 fw-bold text-primary">{{ $pt['jobs'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-2 text-center h-100">
                    <div class="small text-muted">Output ({{ $durationLabel ?? 'Period' }})</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format($pt['period_produced'] ?? 0) }} <span class="fs-6 fw-normal">pcs</span></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-2 text-center h-100">
                    <div class="small text-muted">Target ({{ $durationLabel ?? 'Period' }})</div>
                    <div class="fs-4 fw-bold">{{ number_format($pt['period_target'] ?? 0) }} <span class="fs-6 fw-normal">pcs</span></div>
                    @if(isset($pt['period_achievement_pct']))
                        <div class="small text-muted">{{ $pt['period_achievement_pct'] }}% achieved</div>
                    @endif
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-2 text-center h-100">
                    <div class="small text-muted">Today ({{ $prod['today_label'] ?? '' }})</div>
                    <div class="fs-5 fw-bold text-success">{{ number_format($pt['today_produced'] ?? 0) }}</div>
                    <div class="small">/ {{ number_format($pt['today_target'] ?? 0) }} target
                        @if(isset($pt['today_achievement_pct'])) · {{ $pt['today_achievement_pct'] }}% @endif
                    </div>
                </div>
            </div>
        </div>

        @if(empty($prod['job_rows']))
            <div class="text-center text-muted py-4">
                <i class="bi bi-briefcase fs-2 d-block mb-2 opacity-50"></i>
                No active line jobs with assignments. <a href="{{ route('jobs.index') }}">View jobs</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Job / Line</th>
                            <th>Status</th>
                            <th class="text-end">Job target</th>
                            <th class="text-end">Total produced</th>
                            <th class="text-end">Period output</th>
                            <th class="text-end">Period target</th>
                            <th class="text-end">Today out</th>
                            <th class="text-end">Today target</th>
                            <th class="text-end">Balance</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prod['job_rows'] as $row)
                            @php $job = $row['job']; @endphp
                            <tr class="job-group-row">
                                <td colspan="2">
                                    <strong>{{ $job->job_number }}</strong>
                                    <span class="text-muted small ms-1">{{ $job->product?->name ?? '' }}</span>
                                    @if($job->style_ref)<span class="badge bg-light text-dark border ms-1">{{ $job->style_ref }}</span>@endif
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($row['job_total_target']) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($row['job_total_produced']) }}</td>
                                <td class="text-end text-success fw-semibold">{{ number_format($row['job_period_produced']) }}</td>
                                <td colspan="4"></td>
                                <td>
                                    <a href="{{ route('jobs.show', $job) }}" class="btn btn-sm btn-outline-secondary py-0">Job</a>
                                    <a href="{{ route('hourly-production.create', ['job_id' => $job->id]) }}" class="btn btn-sm btn-outline-primary py-0">Hourly</a>
                                </td>
                            </tr>
                            @foreach($row['lines'] as $line)
                                <tr class="line-indent">
                                    <td class="text-muted">↳ {{ $line['line_name'] }}</td>
                                    <td></td>
                                    <td class="text-end">{{ number_format($line['job_target']) }}</td>
                                    <td class="text-end">{{ number_format($line['total_produced']) }}</td>
                                    <td class="text-end text-success">{{ number_format($line['period_produced']) }}</td>
                                    <td class="text-end">{{ number_format($line['period_target']) }}</td>
                                    <td class="text-end">{{ number_format($line['today_produced']) }}</td>
                                    <td class="text-end">{{ number_format($line['today_target']) }}</td>
                                    <td class="text-end {{ $line['remaining_job'] > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">
                                        {{ number_format($line['remaining_job']) }}
                                    </td>
                                    <td></td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-semibold">
                            <td colspan="2">All lines ({{ $pt['lines'] ?? 0 }})</td>
                            <td class="text-end">{{ number_format($pt['job_target'] ?? 0) }}</td>
                            <td class="text-end">{{ number_format($pt['total_produced'] ?? 0) }}</td>
                            <td class="text-end text-success">{{ number_format($pt['period_produced'] ?? 0) }}</td>
                            <td class="text-end">{{ number_format($pt['period_target'] ?? 0) }}</td>
                            <td class="text-end">{{ number_format($pt['today_produced'] ?? 0) }}</td>
                            <td class="text-end">{{ number_format($pt['today_target'] ?? 0) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="section-card mb-0">
    <div class="card-header">
        <span><i class="bi bi-exclamation-triangle-fill text-danger"></i> Urgent orders</span>
        <a href="{{ route('orders.index', ['urgent' => 1]) }}" class="btn btn-sm btn-outline-danger">View urgent orders</a>
    </div>
    <div class="card-body py-3">
        @if(isset($urgentOrders) && $urgentOrders->count())
            <div class="d-flex flex-wrap gap-2">
                @foreach($urgentOrders as $urgentOrder)
                    <a href="{{ route('orders.index', ['urgent' => 1]) }}" class="btn btn-sm btn-outline-danger">{{ $urgentOrder->order_number }}</a>
                @endforeach
            </div>
            <p class="small text-muted mb-0 mt-2">Open Orders → Urgent Orders for full details.</p>
        @else
            <p class="text-muted mb-0 small">No open urgent orders.</p>
        @endif
    </div>
</div>
</div>{{-- /.dashboard-page --}}

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
  var salesWholesale = {{ number_format($salesWholesale ?? 0, 2, '.', '') }};
  var salesRetail = {{ number_format($salesRetail ?? 0, 2, '.', '') }};
  var salesTotalPeriod = {{ number_format(($salesTotal ?? 0) + ($totalSales ?? 0), 2, '.', '') }};
  var purchaseAmount = {{ number_format($purchaseAmount ?? 0, 2, '.', '') }};

  var salesByTypeCtx = document.getElementById('chartSalesByType');
  if (salesByTypeCtx) {
    var salesSum = salesWholesale + salesRetail;
    new Chart(salesByTypeCtx, {
      type: 'pie',
      data: {
        labels: salesSum > 0 ? ['Wholesale', 'Retail'] : ['No sales'],
        datasets: [{
          data: salesSum > 0 ? [salesWholesale, salesRetail] : [1],
          backgroundColor: ['#6366f1', '#22c55e'],
          hoverBackgroundColor: ['#818cf8', '#4ade80'],
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } },
          tooltip: {
            callbacks: {
              label: function(ctx) {
                var v = ctx.raw;
                var t = ctx.dataset.data.reduce(function(a,b){ return a+b; }, 0);
                var pct = t ? ((v/t)*100).toFixed(1) : 0;
                return ctx.label + ': Rs ' + Number(v).toLocaleString() + ' (' + pct + '%)';
              }
            }
          }
        }
      }
    });
  }

  var purchaseVsSalesCtx = document.getElementById('chartPurchaseVsSales');
  if (purchaseVsSalesCtx) {
    var totalFlow = purchaseAmount + salesTotalPeriod;
    new Chart(purchaseVsSalesCtx, {
      type: 'pie',
      data: {
        labels: totalFlow > 0 ? ['Purchase', 'Sales'] : ['No data'],
        datasets: [{
          data: totalFlow > 0 ? [purchaseAmount, salesTotalPeriod] : [1],
          backgroundColor: ['#8b5cf6', '#06b6d4'],
          hoverBackgroundColor: ['#a78bfa', '#22d3ee'],
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } },
          tooltip: {
            callbacks: {
              label: function(ctx) {
                var v = ctx.raw;
                var t = ctx.dataset.data.reduce(function(a,b){ return a+b; }, 0);
                var pct = t ? ((v/t)*100).toFixed(1) : 0;
                return ctx.label + ': Rs ' + Number(v).toLocaleString() + ' (' + pct + '%)';
              }
            }
          }
        }
      }
    });
  }
})();
</script>
@endsection
