@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold">Add Expense</h4>
            <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>

        <div class="alert alert-info small">
            Monthly bills (Electricity, Water, Rent, …) <strong>topic එකට හරි category</strong> එක select කරලා save කරන්න.
            Cost Report → Net Profit = Collections − මේ categories සියල්ල (direct materials auto from manufacturing).
            @if(auth()->user()?->can_access('manufacturing.costs'))
                <a href="{{ route('reports.show', 'cost-report') }}">Open Cost Report</a>
            @endif
        </div>

        <div class="card">
        <div class="card-body">
            <form action="{{ route('expenses.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category *</label>
                        @include('erp.expenses.partials.category-select', ['selected' => old('expense_category_id')])
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Amount (Rs) *</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Expense Date *</label>
                        <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Reference</label>
                        <input type="text" name="reference" class="form-control" value="{{ old('reference') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Note</label>
                    <textarea name="note" class="form-control" rows="2">{{ old('note') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Expense</button>
            </form>
        </div>
    </div>
</div>
@endsection
