@extends('erp.layout')

@section('content')
<div class="mb-4"><a href="{{ route('salary-components.index') }}" class="text-decoration-none">Salary Components</a></div>
<h4 class="mb-3">Edit Salary Component</h4>
<div class="card border-0 shadow-sm" style="max-width: 480px;">
    <div class="card-body">
        @canRoute('salary-components.update')
<form method="POST" action="{{ route('salary-components.update', $salaryComponent) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $salaryComponent->name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control" value="{{ old('code', $salaryComponent->code) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Type *</label>
                <select name="type" class="form-select" required>
                    <option value="allowance" {{ old('type', $salaryComponent->type) === 'allowance' ? 'selected' : '' }}>Allowance</option>
                    <option value="deduction" {{ old('type', $salaryComponent->type) === 'deduction' ? 'selected' : '' }}>Deduction</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Calculation *</label>
                <select name="calculation_type" class="form-select" required>
                    <option value="fixed" {{ old('calculation_type', $salaryComponent->calculation_type) === 'fixed' ? 'selected' : '' }}>Fixed amount</option>
                    <option value="percentage" {{ old('calculation_type', $salaryComponent->calculation_type) === 'percentage' ? 'selected' : '' }}>Percentage of basic</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Default value *</label>
                <input type="number" name="default_value" class="form-control" value="{{ old('default_value', $salaryComponent->default_value) }}" step="0.01" min="0" required>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" {{ old('is_active', $salaryComponent->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">Active</label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Sort order</label>
                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $salaryComponent->sort_order) }}" min="0">
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('salary-components.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
@endcanRoute
    </div>
</div>
@endsection
