@extends('erp.layout')

@section('content')
<div class="mb-4"><a href="{{ route('leave-types.index') }}" class="text-decoration-none">Leave Types</a></div>
<h4 class="mb-3">Add Leave Type</h4>
<div class="card border-0 shadow-sm" style="max-width: 500px;">
    <div class="card-body">
        <form method="POST" action="{{ route('leave-types.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control" value="{{ old('code') }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Days per year (0 = unlimited)</label>
                <input type="number" name="days_per_year" class="form-control" value="{{ old('days_per_year', 0) }}" min="0">
            </div>
            <div class="mb-3">
                <label class="form-label">Carry-forward days</label>
                <input type="number" name="carry_forward_days" class="form-control" value="{{ old('carry_forward_days', 0) }}" min="0">
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_paid" value="1" class="form-check-input" id="paid" checked>
                    <label class="form-check-label" for="paid">Paid leave</label>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="requires_approval" value="1" class="form-check-input" id="approval" checked>
                    <label class="form-check-label" for="approval">Requires approval</label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('leave-types.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
