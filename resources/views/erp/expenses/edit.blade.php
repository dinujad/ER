@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">Edit Expense</h4>
        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            @canRoute('expenses.update')
<form action="{{ route('expenses.update', $expense) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        @include('erp.expenses.partials.category-select', ['selected' => old('expense_category_id', $expense->expense_category_id)])
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Amount (Rs) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" value="{{ old('amount', $expense->amount) }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Reference</label>
                        <input type="text" name="reference" class="form-control" value="{{ old('reference', $expense->reference) }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Note</label>
                    <textarea name="note" class="form-control" rows="2">{{ old('note', $expense->note) }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
@endcanRoute
        </div>
    </div>
</div>
@endsection
