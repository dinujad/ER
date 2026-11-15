@extends('erp.layout')

@section('content')
<style>
    .rpt-hero { background:linear-gradient(135deg,#1e3a5f 0%,#1a7f5c 100%); color:#fff; border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:1.5rem; }
    .rpt-kpi { border-radius:12px; border:none; box-shadow:0 3px 14px rgba(0,0,0,.07); }
    .rpt-kpi .kpi-val { font-size:1.6rem; font-weight:700; }
    .rpt-kpi .kpi-lbl { font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:#64748b; }
    .tbl-orders thead th { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; background:#f8fafc; }
    @media print {
        .no-print { display:none!important; }
        .tbl-orders thead th { background:#eee; }
    }
</style>

<div class="container-fluid py-3">
    <div class="rpt-hero d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-cart-check me-2"></i>Orders Analysis Report</h4>
            <p class="mb-0 mt-1 opacity-75 small">
                {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
            </p>
        </div>
        <div class="no-print d-flex gap-2 flex-wrap">
            <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
            </a>
            <button onclick="window.print()" class="btn btn-light btn-sm"><i class="bi bi-printer me-1"></i>Print</button>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Reports</a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.show', 'orders-analysis') }}" class="row g-3 align-items-end">
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small">From</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small">To</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small">Sales Person</label>
                    <select name="sales_person_id" class="form-select">
                        <option value="">All Sales Persons</option>
                        @foreach($salesPersons as $sp)
                            <option value="{{ $sp->id }}" {{ $salesPersonId == $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small">Customer</label>
                    <select name="customer_id" class="form-select">
                        <option value="">All Customers</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ $customerId == $c->id ? 'selected' : '' }}>{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach($allStatuses as $key => $meta)
                            <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small">Search</label>
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Order # / customer..." value="{{ $search }}">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card rpt-kpi text-center p-3">
                <div class="kpi-val text-primary">{{ number_format($summary['total_orders']) }}</div>
                <div class="kpi-lbl">Total Orders</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card rpt-kpi text-center p-3">
                <div class="kpi-val text-dark">Rs {{ number_format($summary['total_revenue'], 0) }}</div>
                <div class="kpi-lbl">Total Revenue</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card rpt-kpi text-center p-3">
                <div class="kpi-val text-success">Rs {{ number_format($summary['total_collected'], 0) }}</div>
                <div class="kpi-lbl">Collected</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card rpt-kpi text-center p-3">
                <div class="kpi-val text-danger">Rs {{ number_format($summary['total_due'], 0) }}</div>
                <div class="kpi-lbl">Outstanding</div>
            </div>
        </div>
    </div>

    {{-- Per-Sales-Person Summary (if "All" selected) --}}
    @if(!$salesPersonId && $rows->count() > 0)
    @php
        $byPerson = $rows->groupBy('sales_person');
    @endphp
    <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="bi bi-people me-2 text-primary"></i>Sales Person Summary</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Sales Person</th>
                        <th class="text-center">Orders</th>
                        <th class="text-end">Revenue (Rs)</th>
                        <th class="text-end">Collected (Rs)</th>
                        <th class="text-end">Outstanding (Rs)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byPerson->sortBy(fn($g) => $g->first()['sales_person']) as $person => $group)
                    <tr>
                        <td><strong>{{ $person }}</strong></td>
                        <td class="text-center">{{ $group->count() }}</td>
                        <td class="text-end">{{ number_format($group->sum('total_amount'), 2) }}</td>
                        <td class="text-end text-success">{{ number_format($group->sum('paid_amount'), 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($group->sum('due_amount'), 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Orders Table --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-semibold"><i class="bi bi-list-ul me-2"></i>Order Details
                <span class="badge bg-secondary ms-2">{{ $rows->count() }}</span>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table tbl-orders table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Sales Person</th>
                            <th>Status</th>
                            <th class="text-end">Total (Rs)</th>
                            <th class="text-end">Paid (Rs)</th>
                            <th class="text-end">Due (Rs)</th>
                            <th class="no-print"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        @php
                            $statusColors = ['pending'=>'warning','processing'=>'info','afterchecking'=>'primary','completed'=>'success','cancelled'=>'danger'];
                            $badge = $statusColors[$row['status']] ?? 'secondary';
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $row['order_number'] }}</strong>
                                @if($row['invoice_number'])
                                    <div class="small text-muted">{{ $row['invoice_number'] }}</div>
                                @endif
                            </td>
                            <td class="small">{{ $row['order_date'] }}</td>
                            <td>
                                {{ $row['customer'] }}
                                @if($row['customer_phone'])
                                    <div class="small text-muted">{{ $row['customer_phone'] }}</div>
                                @endif
                            </td>
                            <td class="small">{{ $row['sales_person'] }}</td>
                            <td><span class="badge bg-{{ $badge }}-subtle text-{{ $badge }} border">{{ $row['status_label'] }}</span></td>
                            <td class="text-end fw-semibold">{{ number_format($row['total_amount'], 2) }}</td>
                            <td class="text-end text-success">{{ number_format($row['paid_amount'], 2) }}</td>
                            <td class="text-end {{ $row['due_amount'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                {{ number_format($row['due_amount'], 2) }}
                            </td>
                            <td class="no-print">
                                <a href="{{ route('orders.show', $row['id']) }}" class="btn btn-sm btn-outline-primary py-0">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="bi bi-cart fs-1 d-block mb-2"></i>
                                No orders found for the selected filters.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="5" class="text-end">Totals:</td>
                            <td class="text-end">Rs {{ number_format($summary['total_revenue'], 2) }}</td>
                            <td class="text-end text-success">Rs {{ number_format($summary['total_collected'], 2) }}</td>
                            <td class="text-end text-danger">Rs {{ number_format($summary['total_due'], 2) }}</td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
