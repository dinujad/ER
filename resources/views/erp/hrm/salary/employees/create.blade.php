@extends('erp.layout')

@section('content')
<div class="mb-4"><a href="{{ route('employee-salaries.index') }}" class="text-decoration-none">Employee Salaries</a></div>
<h4 class="mb-3">Set / Update Salary</h4>
<div class="card border-0 shadow-sm" style="max-width: 480px;">
    <div class="card-body">
        <form method="POST" action="{{ route('employee-salaries.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Employee *</label>
                <select name="employee_id" class="form-select" required>
                    <option value="">Select employee</option>
                    @foreach($employees as $e)
                    <option value="{{ $e->id }}" {{ old('employee_id', $employee->id ?? null) == $e->id ? 'selected' : '' }}>{{ $e->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Basic salary *</label>
                <input type="number" name="basic_salary" class="form-control" value="{{ old('basic_salary') }}" step="0.01" min="0" required>
                @error('basic_salary')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Effective from *</label>
                <input type="date" name="effective_from" class="form-control" value="{{ old('effective_from', now()->toDateString()) }}" required>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('employee-salaries.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
