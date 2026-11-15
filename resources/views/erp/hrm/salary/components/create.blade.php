@extends('erp.layout')

@section('content')
<div class="mb-4"><a href="{{ route('salary-components.index') }}" class="text-decoration-none">Salary Components</a></div>
<h4 class="mb-3">Add Salary Component</h4>
<div class="card border-0 shadow-sm" style="max-width: 480px;">
    <div class="card-body">
        <form method="POST" action="{{ route('salary-components.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Transport Allowance" required>
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="e.g. TA">
            </div>
            <div class="mb-3">
                <label class="form-label">Type *</label>
                <select name="type" class="form-select" required>
                    <option value="allowance" {{ old('type') === 'allowance' ? 'selected' : '' }}>Allowance</option>
                    <option value="deduction" {{ old('type') === 'deduction' ? 'selected' : '' }}>Deduction</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Calculation *</label>
                <select name="calculation_type" class="form-select" required>
                    <option value="fixed" {{ old('calculation_type', 'fixed') === 'fixed' ? 'selected' : '' }}>Fixed amount</option>
                    <option value="percentage" {{ old('calculation_type') === 'percentage' ? 'selected' : '' }}>Percentage of basic</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Default value *</label>
                <input type="number" name="default_value" class="form-control" value="{{ old('default_value', 0) }}" step="0.01" min="0" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Sort order</label>
                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('salary-components.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
