@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">Expenses</h4>
        <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">Add Expense</a>
    </div>

    <p class="text-muted small">Monthly bills (Electricity, Water, Rent…) go here. Cost Report uses <strong>Simple Average</strong>: total expenses ÷ finished pieces.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Amount (Rs)</th>
                        <th>Reference</th>
                        <th>Note</th>
                        <th>Added By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $e)
                        <tr>
                            <td>{{ $e->expense_date->format('d M Y') }}</td>
                            <td>{{ $e->category?->name }}</td>
                            <td>{{ number_format($e->amount, 2) }}</td>
                            <td>{{ $e->reference ?? '—' }}</td>
                            <td>{{ Str::limit($e->note, 40) }}</td>
                            <td>{{ $e->creator?->name ?? '—' }}</td>
                            <td>
                                @canRoute('expenses.edit')
<a href="{{ route('expenses.edit', $e) }}" class="btn btn-sm btn-outline-primary">Edit</a>
@endcanRoute
                                @canRoute('expenses.destroy')
<form action="{{ route('expenses.destroy', $e) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this expense?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
@endcanRoute
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted text-center">No expenses.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages())
            <div class="card-footer">{{ $expenses->links() }}</div>
        @endif
    </div>

    <div class="mt-2">
        <a href="{{ route('reports.show', ['report' => 'expense']) }}" class="btn btn-outline-secondary btn-sm">View Expense Report</a>
    </div>
</div>
@endsection
