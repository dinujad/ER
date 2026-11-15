@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Customers</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('erp.customers.create', ['type' => 'wholesale']) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus me-1"></i> Add Customer
            </a>
            <a href="{{ route('orders.create') }}" class="btn btn-success btn-sm">
                <i class="bi bi-cart-plus me-1"></i> Create Order
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ ($tab ?? 'retail') === 'retail' ? 'active' : '' }}" href="{{ route('erp.customers.index', array_merge(request()->except('tab', 'page'), ['tab' => 'retail'])) }}">
                <i class="bi bi-person me-1"></i>Retail Customers
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ ($tab ?? '') === 'wholesale' ? 'active' : '' }}" href="{{ route('erp.customers.index', array_merge(request()->except('tab', 'page'), ['tab' => 'wholesale'])) }}">
                <i class="bi bi-shop me-1"></i>Wholesale Customers
            </a>
        </li>
    </ul>

    {{-- Advanced Search --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2">
            <span class="fw-semibold small"><i class="bi bi-search me-1 text-primary"></i>Advanced Search</span>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('erp.customers.index') }}" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="{{ $tab ?? 'retail' }}">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Name, phone, email, business, BR…">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Phone</label>
                    <input type="text" name="phone" class="form-control form-control-sm" value="{{ request('phone') }}" placeholder="Phone number">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Email</label>
                    <input type="text" name="email" class="form-control form-control-sm" value="{{ request('email') }}" placeholder="Email">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">
                        @if(($tab ?? '') === 'wholesale')
                            Added by (Sales person)
                        @else
                            Added by
                        @endif
                    </label>
                    <select name="added_by" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="system" {{ request('added_by') === 'system' ? 'selected' : '' }}>System / POS</option>
                        @foreach($salesPersons as $sp)
                            <option value="{{ $sp->id }}" {{ (string) request('added_by') === (string) $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label form-label-sm mb-1">Added from</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label form-label-sm mb-1">Added to</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-12 d-flex gap-2 align-items-center mt-1">
                    <a href="{{ route('erp.customers.index', ['tab' => $tab ?? 'retail']) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @if(request()->hasAny(['search', 'phone', 'email', 'added_by', 'date_from', 'date_to']))
                        <span class="small text-muted">
                            Showing <strong>{{ $customers->total() }}</strong> customer(s) matching filters.
                        </span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="m-0 font-weight-bold text-primary">
                @if(($tab ?? 'retail') === 'wholesale')
                    Wholesale Customer List
                @else
                    Retail Customer List
                @endif
            </h6>
            <span class="badge bg-light text-dark border">Total: {{ $customers->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Business / Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Type</th>
                            <th>Added By</th>
                            <th>Orders</th>
                            <th>Added On</th>
                            @if(($tab ?? 'retail') === 'wholesale')
                                <th>Return credit</th>
                            @endif
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $customer->display_name }}</div>
                                @if($customer->business_name && $customer->name && strcasecmp($customer->business_name, $customer->name) !== 0)
                                    <div class="small text-muted">Contact: {{ $customer->name }}</div>
                                @endif
                            </td>
                            <td>{{ $customer->email ?: '—' }}</td>
                            <td>{{ $customer->phone ?: '—' }}</td>
                            <td class="small">{{ Str::limit($customer->address ?: '—', 40) }}</td>
                            <td>
                                @if($customer->customer_type === 'wholesale')
                                    <span class="badge bg-info">Wholesale</span>
                                @else
                                    <span class="badge bg-secondary">Retail</span>
                                @endif
                            </td>
                            <td>
                                @if($customer->addedBy)
                                    <span class="badge bg-info text-dark">{{ $customer->addedBy->name }}</span>
                                    <small class="d-block text-muted">Sales Person</small>
                                @else
                                    <span class="badge bg-secondary">System / POS</span>
                                @endif
                            </td>
                            <td>
                                @if($customer->orders_count > 0)
                                    <span class="badge bg-primary">{{ $customer->orders_count }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $customer->created_at->format('Y-m-d') }}</td>
                            @if(($tab ?? 'retail') === 'wholesale')
                                @include('erp.customers._return_credit_cell', [
                                    'customer' => $customer,
                                    'previousReturnAction' => route('erp.customers.previous-return.store', $customer),
                                ])
                            @endif
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($customer->customer_type === 'wholesale')
                                        <a href="{{ route('erp.customers.show', $customer->id) }}" class="btn btn-sm btn-outline-primary">Profile</a>
                                    @endif
                                    <a href="{{ route('erp.customers.edit', $customer) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                    @if($customer->orders_count > 0)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Has orders — cannot delete">Delete</button>
                                    @else
                                        @canRoute('erp.customers.destroy')
<form method="POST" action="{{ route('erp.customers.destroy', $customer) }}"
                                              onsubmit="return confirm('Delete customer &quot;{{ addslashes($customer->display_name) }}&quot;? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
@endcanRoute
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ ($tab ?? 'retail') === 'wholesale' ? 10 : 9 }}" class="text-center text-muted py-5">No customers match these filters.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($customers->hasPages())
                <div class="card-footer bg-white border-top-0">{{ $customers->links() }}</div>
            @endif
        </div>
    </div>
</div>
@if(($tab ?? 'retail') === 'wholesale')
    @include('erp.customers._previous_return_modal')
@endif
@endsection
