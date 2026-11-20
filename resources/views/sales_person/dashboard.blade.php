@extends('sales_person.layout')

@section('content')
<style>
    .dashboard-hero { background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); color: #fff; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 10px 40px rgba(30,58,95,0.2); }
    .dashboard-hero h1 { font-weight: 700; font-size: 1.35rem; margin: 0; letter-spacing: -0.02em; }
    .dashboard-hero .text-opacity-90 { opacity: 0.9; font-size: 0.875rem; }
    .kpi-card { border: none; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); transition: transform 0.2s, box-shadow 0.2s; overflow: hidden; }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,0.1); }
    .kpi-card .card-body { padding: 1rem 1.25rem; }
    .kpi-card .kpi-icon { width: 44px; height: 44px; min-width: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
    .kpi-card .kpi-value { font-size: 1.5rem; font-weight: 700; color: #1a1a2e; line-height: 1.2; }
    .kpi-card .kpi-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 0.2rem; }
    .section-card { border: none; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 1.25rem; overflow: hidden; }
    .section-card .card-header { background: #fff; border-bottom: 1px solid #eee; padding: 0.875rem 1.25rem; font-weight: 600; font-size: 0.95rem; color: #1a1a2e; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
    .section-card .card-header .bi { font-size: 1rem; color: #4e73df; margin-right: 0.35rem; }
    .section-card .table { margin: 0; font-size: 0.875rem; }
    .section-card .table th { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6c757d; font-weight: 600; padding: 0.6rem 0.75rem; }
    .section-card .table td { padding: 0.6rem 0.75rem; vertical-align: middle; }
    .section-card .table tbody tr:hover { background-color: #f8fafc; }
    .link-card { color: inherit; text-decoration: none; display: block; }
    .link-card:hover { color: inherit; }
    @media (max-width: 576px) {
        .dashboard-hero { padding: 1rem; }
        .dashboard-hero h1 { font-size: 1.2rem; }
        .kpi-card .kpi-value { font-size: 1.35rem; }
        .kpi-card .card-body { padding: 0.875rem 1rem; }
    }
</style>

@if(!empty($routeCash) && !empty($cashRun))
<div class="card section-card mb-3">
    <div class="card-header">
        <span><i class="bi bi-cash-stack"></i> Petty cash — {{ $cashRun->salesRoute?->name ?? 'Route' }}</span>
        <span class="badge {{ $cashRun->isActive() ? 'bg-success' : 'bg-secondary' }}">{{ $cashRun->isActive() ? 'On route' : 'Last route' }}</span>
    </div>
    <div class="card-body">
        <div class="row g-3 text-center">
            <div class="col-6 col-md-3">
                <div class="small text-muted">Petty cash given</div>
                <div class="fs-4 fw-bold">Rs {{ number_format($routeCash->petty, 2) }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="small text-muted">Cash collected</div>
                <div class="fs-4 fw-bold">Rs {{ number_format($routeCash->collected, 2) }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="small text-muted">Expenses</div>
                <div class="fs-4 fw-bold">Rs {{ number_format($routeCash->expenses, 2) }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="small text-muted">Handover to office</div>
                <div class="fs-4 fw-bold {{ $routeCash->handover < 0 ? 'text-danger' : 'text-success' }}">Rs {{ number_format($routeCash->handover, 2) }}</div>
            </div>
        </div>
        <p class="small text-muted mb-0 mt-2">Handover = petty cash + cash collected − expenses.</p>
    </div>
</div>
@endif

<div class="dashboard-hero d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1>Sales Dashboard</h1>
        <p class="text-opacity-90 mb-0 mt-1">Your customers and orders at a glance</p>
    </div>
    <div class="text-end text-opacity-90 small">
        {{ now()->format('l, F j, Y') }}
    </div>
</div>

@if(($assignedRoute ?? null) || ($activeRouteRun ?? null))
<div class="card section-card mb-4">
    <div class="card-header">
        <span><i class="bi bi-signpost-2"></i> Today's route</span>
        <a href="{{ route('sales-person.route.show') }}" class="btn btn-sm btn-outline-primary">Open My Route</a>
    </div>
    <div class="card-body">
        @if($assignedRoute)
            <div class="fw-semibold fs-5 mb-1">{{ $assignedRoute->name }}</div>
            @if($assignedRoute->code)
                <div class="text-muted small mb-3">{{ $assignedRoute->code }}</div>
            @else
                <div class="mb-3"></div>
            @endif
        @else
            <p class="text-muted mb-3">No route assigned yet.</p>
        @endif

        @if($activeRouteRun)
            <div class="alert alert-success py-2 mb-3">
                Started at <strong>{{ $activeRouteRun->startedLabel('h:i A') }}</strong>
                · {{ $activeRouteRun->durationLabel() }}
                · Trip expenses Rs {{ number_format((float) $activeRouteRun->expenses->sum('amount'), 2) }}
            </div>
            <div class="small mb-2">
                <span class="text-muted">Driver:</span> <strong>{{ $activeRouteRun->driver_name ?: '—' }}</strong>
                @if($activeRouteRun->assistants)
                    <span class="text-muted ms-2">Assistants:</span> <strong>{{ $activeRouteRun->assistants }}</strong>
                @endif
            </div>
            <p class="small text-muted mb-2">Add expenses below — they attach to this started route.</p>
            <form method="POST" action="{{ route('sales-person.route.complete') }}" onsubmit="if (!confirm('Complete this route now?')) return false; this.querySelector('button').disabled = true;">
                @csrf
                <button type="submit" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-check2-circle me-1"></i> Complete route
                </button>
            </form>
        @elseif($assignedRoute && $assignedRoute->is_active)
            <form method="POST" action="{{ route('sales-person.route.start') }}" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <div class="mb-2">
                    <label class="form-label small mb-1">Driver name <span class="text-danger">*</span></label>
                    <input type="text" name="driver_name" class="form-control @error('driver_name') is-invalid @enderror" value="{{ old('driver_name') }}" required placeholder="Driver with you today">
                    @error('driver_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small mb-1">Assistants</label>
                    <input type="text" name="assistants" class="form-control @error('assistants') is-invalid @enderror" value="{{ old('assistants') }}" placeholder="Kamal, Nimal, Sunil">
                    @error('assistants')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-play-circle me-1"></i> Start route
                </button>
            </form>
        @elseif($assignedRoute)
            <div class="alert alert-warning py-2 mb-0">This route is inactive. Ask admin to activate it before starting.</div>
        @endif
    </div>
</div>
@endif

<div class="card section-card mb-4">
    <div class="card-header">
        <span><i class="bi bi-lightning-charge-fill"></i> Quick Order — Search Product</span>
        <a href="{{ route('sales-person.orders.create') }}" class="btn btn-sm btn-primary">New Order</a>
    </div>
    <div class="card-body">
        @include('sales_person.partials.product-search', ['mode' => 'dashboard'])
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md">
        <a href="{{ route('sales-person.customers.index') }}" class="link-card">
            <div class="card kpi-card h-100">
                <div class="card-body d-flex align-items-center gap-2 gap-md-3">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="kpi-value">{{ $customersCount }}</div>
                        <div class="kpi-label">My Customers</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md">
        <a href="{{ route('sales-person.orders.index') }}" class="link-card">
            <div class="card kpi-card h-100">
                <div class="card-body d-flex align-items-center gap-2 gap-md-3">
                    <div class="kpi-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-cart-check-fill"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="kpi-value">{{ $ordersCount }}</div>
                        <div class="kpi-label">Total Orders</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md">
        <a href="{{ route('sales-person.sales.index') }}" class="link-card">
            <div class="card kpi-card h-100">
                <div class="card-body d-flex align-items-center gap-2 gap-md-3">
                    <div class="kpi-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-calendar-month"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="kpi-value">{{ $ordersThisMonth }}</div>
                        <div class="kpi-label">Orders This Month</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md">
        <a href="#pending-checks" class="link-card">
            <div class="card kpi-card h-100 {{ ($pendingChequeAmount ?? 0) > 0 ? 'border border-warning' : '' }}">
                <div class="card-body d-flex align-items-center gap-2 gap-md-3">
                    <div class="kpi-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="kpi-value {{ ($pendingChequeAmount ?? 0) > 0 ? 'text-warning' : '' }}">
                            {{ number_format($pendingChequeAmount ?? 0, 0) }}
                        </div>
                        <div class="kpi-label">Pending Cheques</div>
                        @if(($pendingChequeCount ?? 0) > 0)
                            <div class="small text-muted">{{ $pendingChequeCount }} cheque{{ $pendingChequeCount === 1 ? '' : 's' }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md">
        <a href="{{ route('sales-person.expenses.index') }}" class="link-card">
            <div class="card kpi-card h-100">
                <div class="card-body d-flex align-items-center gap-2 gap-md-3">
                    <div class="kpi-icon bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="kpi-value">{{ number_format($expensesThisMonthTotal, 0) }}</div>
                        <div class="kpi-label">Expenses (month)</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

@if(($pendingChequeCount ?? 0) > 0)
<div class="card section-card mb-4 border-warning" id="pending-checks">
    <div class="card-header bg-warning bg-opacity-10">
        <span><i class="bi bi-hourglass-split text-warning"></i> Pending cheques (not deposited to bank)</span>
        <span class="badge bg-warning text-dark">Rs {{ number_format($pendingChequeAmount, 2) }}</span>
    </div>
    <div class="card-body p-0">
        @if(($pendingChequesByCustomer ?? collect())->isNotEmpty())
        <div class="px-3 pt-3">
            <div class="small text-muted mb-2">By your customer (business name)</div>
            <div class="d-flex flex-wrap gap-2 mb-2">
                @foreach($pendingChequesByCustomer as $group)
                    <span class="badge bg-light text-dark border px-3 py-2">
                        @if($group->customer && $group->customer->customer_type === 'wholesale' && (int) $group->customer->added_by === (int) auth()->id())
                            <a href="{{ route('sales-person.customers.show', $group->customer) }}" class="text-decoration-none text-dark">
                                {{ $group->business_name }}
                            </a>
                        @else
                            {{ $group->business_name }}
                        @endif
                        · {{ $group->cheque_count }} · <strong class="text-warning">Rs {{ number_format($group->amount, 2) }}</strong>
                    </span>
                @endforeach
            </div>
        </div>
        @endif
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Order #</th>
                        <th class="d-none d-md-table-cell">Cheque date</th>
                        <th class="text-end">Amount</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingChequePayments as $payment)
                    @php $order = $payment->order; $customer = $order?->customer; @endphp
                    <tr>
                        <td>
                            @if($customer && $customer->customer_type === 'wholesale' && (int) $customer->added_by === (int) auth()->id())
                                <a href="{{ route('sales-person.customers.show', $customer) }}" class="text-decoration-none fw-semibold">{{ $customer->display_name }}</a>
                            @else
                                <span class="fw-semibold">{{ $customer?->display_name ?? '—' }}</span>
                            @endif
                        </td>
                        <td>{{ $order?->order_number ?? '—' }}</td>
                        <td class="d-none d-md-table-cell small">{{ $payment->cheque_date?->format('Y-m-d') ?? $payment->payment_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="text-end font-monospace text-warning fw-semibold">{{ number_format((float) $payment->amount, 2) }}</td>
                        <td class="text-end">
                            @if($order)
                                <a href="{{ route('sales-person.orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">Order</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-3 py-2 small text-muted border-top">
            Cheque ලැබුණත් bank deposit නොකළ amount. Admin Deposit/Clear කරනකල් මෙතැන පෙනෙනවා.
        </div>
    </div>
</div>
@endif

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card section-card">
            <div class="card-header">
                <span><i class="bi bi-list-ul"></i> Recent Orders</span>
                <a href="{{ route('sales-person.orders.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
            <div class="card-body p-0">
                @if($recentOrders->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-cart fs-1"></i>
                        <p class="mb-0 mt-2">No orders yet</p>
                        <a href="{{ route('sales-person.orders.create') }}" class="btn btn-primary btn-sm mt-2">Create order</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th class="d-none d-md-table-cell">Date</th>
                                    <th>Amount</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                <tr>
                                    <td><strong>{{ $order->order_number }}</strong></td>
                                    <td>
                                        @if($order->customer)
                                            @if($order->customer->customer_type === 'wholesale' && (int) $order->customer->added_by === (int) auth()->id())
                                                <a href="{{ route('sales-person.customers.show', $order->customer_id) }}" class="text-decoration-none">{{ $order->customer->display_name }}</a>
                                            @else
                                                {{ $order->customer->display_name }}
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="d-none d-md-table-cell">{{ $order->order_date?->format('M d, Y') ?? '—' }}</td>
                                    <td>{{ number_format($order->invoice_total, 2) }}</td>
                                    <td>
                                        <a href="{{ route('sales-person.orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">View</a>
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
    <div class="col-12 col-lg-4">
        <div class="card section-card">
            <div class="card-header">
                <span><i class="bi bi-lightning-charge"></i> Quick actions</span>
            </div>
            <div class="card-body">
                <a href="{{ route('sales-person.orders.create') }}" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-plus-lg me-2"></i> New Order
                </a>
                <a href="{{ route('sales-person.customers.create') }}" class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-person-plus me-2"></i> Add Customer
                </a>
                <a href="{{ route('sales-person.customers.index') }}" class="btn btn-outline-secondary w-100 mb-2">
                    <i class="bi bi-people me-2"></i> Customer List
                </a>
                <a href="{{ route('sales-person.sales.index') }}" class="btn btn-outline-secondary w-100 mb-2">
                    <i class="bi bi-graph-up-arrow me-2"></i> Sales & Commission
                </a>
                <a href="{{ route('sales-person.expenses.index') }}" class="btn btn-outline-warning w-100 mb-3">
                    <i class="bi bi-receipt me-2"></i> My Expenses
                </a>
            </div>
        </div>

        @php $expenseRedirect = route('sales-person.dashboard', [], false) . '#expenses'; @endphp
        @include('sales_person.partials.expense-form', ['redirectTo' => $expenseRedirect])

        @if($recentExpenses->isNotEmpty())
        <div class="card section-card mt-3">
            <div class="card-header py-2">
                <span class="small"><i class="bi bi-clock-history"></i> Recent expenses</span>
                <a href="{{ route('sales-person.expenses.index') }}" class="btn btn-sm btn-link">All</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    @foreach($recentExpenses as $ex)
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <span>{{ optional($ex->expense_date)->format('M d') ?? '—' }} · {{ $ex->description }}</span>
                        <span class="d-flex align-items-center gap-2">
                            @include('partials.expense-receipt-link', ['expense' => $ex])
                            <strong class="text-nowrap">Rs {{ number_format($ex->amount, 2) }}</strong>
                        </span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    document.querySelectorAll('.sp-expense-type').forEach(function (sel) {
        var form = sel.closest('form');
        if (!form) return;
        var desc = form.querySelector('.sp-expense-desc');
        if (!desc) return;
        var labels = { fuel: 'Fuel', travel: 'Travel', food: 'Food', accommodation: 'Accommodation', supplies: 'Supplies', others: 'Other expense' };
        sel.addEventListener('change', function () {
            var v = labels[sel.value];
            if (v && (!desc.value || Object.values(labels).indexOf(desc.value) >= 0)) {
                desc.value = v;
            }
        });
    });
})();
</script>
@endsection
