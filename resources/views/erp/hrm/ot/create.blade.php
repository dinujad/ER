@extends('erp.layout')

@section('content')
<div class="mb-4"><a href="{{ route('ot-requests.index') }}" class="text-decoration-none">Overtime</a></div>
<h4 class="mb-3">Request overtime</h4>
<div class="card border-0 shadow-sm" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('ot-requests.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Employee</label>
                <select name="employee_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" {{ old('employee_id') == $e->id ? 'selected' : '' }}>
                            {{ $e->display_name ?: $e->name }}@if($e->department) · {{ $e->department->name }}@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="work_date" class="form-control" value="{{ old('work_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Type</label>
                    <select name="category" class="form-select" required>
                        <option value="workday">Workday</option>
                        <option value="weekend" {{ old('category') === 'weekend' ? 'selected' : '' }}>Weekend</option>
                        <option value="holiday" {{ old('category') === 'holiday' ? 'selected' : '' }}>Holiday</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Planned hours</label>
                    <input type="number" name="planned_hours" class="form-control" min="0.5" max="16" step="0.5" value="{{ old('planned_hours', 2) }}" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" rows="2">{{ old('reason') }}</textarea>
            </div>
            <p class="small text-muted">Rate comes from the employee's shift: workday, weekend, or holiday. Hours above the shift daily limit are flagged.</p>
            <button class="btn btn-primary">Submit</button>
            <a href="{{ route('ot-requests.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
