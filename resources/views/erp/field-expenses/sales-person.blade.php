@extends('erp.layout')

@section('title', 'Sales Person Expenses')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Sales Person Expenses</h1>
            <p class="text-muted small mb-0">Field expenses submitted by sales persons — view receipts / slips.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-primary-subtle text-primary border px-3 py-2">Total: <strong>Rs {{ number_format($totalAmount, 2) }}</strong></span>
            <span class="badge bg-success-subtle text-success border px-3 py-2">With receipt: <strong>{{ $withSlipCount }}</strong></span>
            @canRoute('field-expenses.sales-person.delete-today')
<form method="POST" action="{{ route('field-expenses.sales-person.delete-today') }}" class="d-inline" onsubmit="return confirm('Delete all of today\'s expenses? This cannot be undone.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-danger" @disabled($todayCount < 1)>Delete today's expenses{{ $todayCount > 0 ? ' ('.$todayCount.')' : '' }}</button>
            </form>
@endcanRoute
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Month</label>
                    <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($monthOptions as $opt)
                            <option value="{{ $opt['value'] }}" @selected($month === $opt['value'])>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Sales Person</label>
                    <select name="sales_person_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($salesPersons as $sp)
                            <option value="{{ $sp->id }}" @selected((string) request('sales_person_id') === (string) $sp->id)>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach(\App\Models\SalesPersonExpense::typeOptions() as $key => $label)
                            <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Receipt</label>
                    <select name="has_slip" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="yes" @selected(request('has_slip') === 'yes')>With receipt</option>
                        <option value="no" @selected(request('has_slip') === 'no')>No receipt</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    <a href="{{ route('field-expenses.sales-person.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Sales Person</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th>Receipt</th>
                        <th>Notes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td class="text-nowrap">{{ $expense->expense_date->format('d M Y') }}</td>
                            <td>{{ $expense->user?->name ?? '—' }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $expense->typeLabel() }}</span></td>
                            <td>{{ $expense->description }}</td>
                            <td class="text-end font-monospace">{{ number_format($expense->amount, 2) }}</td>
                            <td>@include('partials.expense-receipt-link', ['expense' => $expense])</td>
                            <td class="small text-muted">{{ $expense->notes ?: '—' }}</td>
                            <td class="text-end">
                                @canRoute('field-expenses.sales-person.destroy')
<form method="POST" action="{{ route('field-expenses.sales-person.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
@endcanRoute
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">No expenses for {{ $start->format('F Y') }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages())
            <div class="card-footer bg-white border-top-0">{{ $expenses->links() }}</div>
        @endif
    </div>
</div>
@endsection
