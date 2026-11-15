@extends('erp.layout')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h4 class="mb-0">Suppliers</h4>
    <a href="{{ route('suppliers.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Add Supplier</a>
</div>

<form method="GET" action="{{ route('suppliers.index') }}" class="card mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-0">Search (name, phone, code)</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Search...">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="all">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Outstanding</label>
                <select name="outstanding" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="yes" {{ request('outstanding') === 'yes' ? 'selected' : '' }}>Has balance due</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary btn-sm w-100">Filter</button>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Phone</th>
                        <th>Purchases</th>
                        <th>Outstanding</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $s)
                    <tr>
                        <td><span class="text-muted fw-medium">{{ $s->code }}</span></td>
                        <td><strong>{{ $s->name }}</strong></td>
                        <td>{{ $s->company_name ?? '—' }}</td>
                        <td>{{ $s->phone }}</td>
                        <td>{{ $s->purchases_count ?? 0 }}</td>
                        <td class="{{ $s->outstanding_balance > 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format($s->outstanding_balance, 2) }}</td>
                        <td><span class="badge bg-{{ $s->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($s->status) }}</span></td>
                        <td>
                            <a href="{{ route('suppliers.show', $s) }}" class="btn btn-sm btn-outline-info">View</a>
                            @canRoute('suppliers.edit')
<a href="{{ route('suppliers.edit', $s) }}" class="btn btn-sm btn-outline-primary">Edit</a>
@endcanRoute
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No suppliers found. <a href="{{ route('suppliers.create') }}">Add supplier</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0">{{ $suppliers->links() }}</div>
    </div>
</div>
@endsection
