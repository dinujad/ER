@extends('erp.layout')

@section('content')
<div class="mb-4"><a href="{{ route('leave-types.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Leave Types</a></div>
<h4 class="mb-3">Edit Leave Type</h4>
<div class="card border-0 shadow-sm" style="max-width: 500px;">
    <div class="card-body">
        @canRoute('leave-types.update')
<form method="POST" action="{{ route('leave-types.update', $leaveType) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $leaveType->name) }}" required>
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control" value="{{ old('code', $leaveType->code) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Days per year</label>
                <input type="number" name="days_per_year" class="form-control" value="{{ old('days_per_year', $leaveType->days_per_year) }}" min="0">
            </div>
            <div class="mb-3">
                <label class="form-label">Carry-forward days</label>
                <input type="number" name="carry_forward_days" class="form-control" value="{{ old('carry_forward_days', $leaveType->carry_forward_days ?? 0) }}" min="0">
            </div>
            <div class="mb-3">
                <div class="form-check"><input type="checkbox" name="is_paid" value="1" class="form-check-input" id="paid" {{ old('is_paid', $leaveType->is_paid) ? 'checked' : '' }}><label class="form-check-label" for="paid">Paid leave</label></div>
            </div>
            <div class="mb-3">
                <div class="form-check"><input type="checkbox" name="requires_approval" value="1" class="form-check-input" id="approval" {{ old('requires_approval', $leaveType->requires_approval) ? 'checked' : '' }}><label class="form-check-label" for="approval">Requires approval</label></div>
            </div>
            <div class="mb-3">
                <div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" {{ old('is_active', $leaveType->is_active) ? 'checked' : '' }}><label class="form-check-label" for="active">Active</label></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $leaveType->description) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('leave-types.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
@endcanRoute
    </div>
</div>
@endsection
