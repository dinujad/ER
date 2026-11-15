@extends('sales_person.layout')

@section('content')
<style>
    .sp-search-card { border-radius:12px; border:none; box-shadow:0 3px 14px rgba(0,0,0,.07); }
    .sp-search-toggle { cursor:pointer; user-select:none; }
    .sp-search-toggle .bi-chevron-down { transition:transform .2s; }
    .sp-search-toggle[aria-expanded="true"] .bi-chevron-down { transform:rotate(180deg); }
    @media (max-width:576px) {
        .sp-table th:nth-child(2), .sp-table td:nth-child(2),
        .sp-table th:nth-child(4), .sp-table td:nth-child(4) { display:none; }
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h3 mb-0 text-gray-800">My Customers</h1>
        <a href="{{ route('sales-person.customers.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i>Add New Customer
        </a>
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

    {{-- Advanced Search --}}
    <div class="card sp-search-card mb-4">
        <div class="card-header bg-white py-3 sp-search-toggle d-flex align-items-center justify-content-between"
             data-bs-toggle="collapse" data-bs-target="#customerSearchPanel" aria-expanded="{{ request()->hasAny(['q','phone','email','business_name','date_from','date_to']) ? 'true' : 'false' }}">
            <span class="fw-semibold"><i class="bi bi-search me-2 text-primary"></i>Advanced Search</span>
            <i class="bi bi-chevron-down text-muted"></i>
        </div>
        <div class="collapse {{ request()->hasAny(['q','phone','email','business_name','date_from','date_to']) ? 'show' : '' }}" id="customerSearchPanel">
            <div class="card-body border-top">
                <form method="GET" action="{{ route('sales-person.customers.index') }}" class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold small">Search (Name / Phone / Email / BR)</label>
                        <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Name, phone, email, business, BR number...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold small">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ request('phone') }}" placeholder="07XXXXXXXX">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold small">Email</label>
                        <input type="text" name="email" class="form-control" value="{{ request('email') }}" placeholder="email@...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold small">Business Name</label>
                        <input type="text" name="business_name" class="form-control" value="{{ request('business_name') }}" placeholder="Shop / company">
                    </div>
                    <div class="col-6 col-md-1">
                        <label class="form-label fw-semibold small">From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-6 col-md-1">
                        <label class="form-label fw-semibold small">To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-12 col-md-12 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
                        <a href="{{ route('sales-person.customers.index') }}" class="btn btn-outline-secondary">Clear</a>
                        @if(request()->hasAny(['q','phone','email','business_name','date_from','date_to']))
                            <span class="align-self-center small text-muted ms-auto">
                                <strong>{{ $customers->total() }}</strong> customer(s) found
                            </span>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Customer List</h6>
            <span class="badge bg-light text-dark border">Total: {{ $customers->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 sp-table">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Added by</th>
                            <th>Added On</th>
                            <th>Return credit</th>
                            <th></th>
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
                            <td class="small">
                                @if($customer->added_by == auth()->id())
                                    <span class="text-muted">You</span>
                                @else
                                    {{ $customer->addedBy?->name ?? '—' }}
                                @endif
                            </td>
                            <td class="small text-muted">{{ $customer->created_at->format('Y-m-d') }}</td>
                            @include('erp.customers._return_credit_cell', [
                                'customer' => $customer,
                                'previousReturnAction' => route('sales-person.customers.previous-return.store', $customer),
                            ])
                            <td>
                                <a href="{{ route('sales-person.customers.show', $customer) }}" class="btn btn-sm btn-outline-primary">View</a>
                                <a href="{{ route('sales-person.customers.edit', $customer) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-people fs-1 d-block mb-2"></i>
                                @if(request()->hasAny(['q','phone','email','business_name','date_from','date_to']))
                                    No customers match your search.
                                @else
                                    No customers yet. Add your first customer.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($customers->hasPages())
                <div class="card-footer bg-white">{{ $customers->links() }}</div>
            @endif
        </div>
    </div>
</div>
@include('erp.customers._previous_return_modal')
@endsection
