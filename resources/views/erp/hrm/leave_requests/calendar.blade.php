@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Leave calendar</h4>
    <a href="{{ route('leave-requests.index') }}" class="btn btn-outline-secondary">Back</a>
</div>
<form method="GET" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3 row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Month</label>
            <input type="month" name="month" class="form-control" value="{{ $month->format('Y-m') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
                <option value="">All</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Production line</label>
            <select name="production_line_id" class="form-select">
                <option value="">All</option>
                @foreach($lines as $line)
                    <option value="{{ $line->id }}" {{ request('production_line_id') == $line->id ? 'selected' : '' }}>{{ $line->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div>
    </div>
</form>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Employee</th><th>Department</th><th>Line</th><th>Type</th><th>From</th><th>To</th><th>Days</th></tr></thead>
            <tbody>
                @forelse($leaves as $leave)
                <tr>
                    <td>{{ $leave->employee?->display_name ?: $leave->employee?->name }}</td>
                    <td>{{ $leave->employee?->department?->name ?? '—' }}</td>
                    <td>{{ $leave->employee?->productionLine?->name ?? '—' }}</td>
                    <td>{{ $leave->leaveType?->name }} @if($leave->leaveType && ! $leave->leaveType->is_paid)<span class="badge bg-secondary">Unpaid</span>@endif</td>
                    <td>{{ $leave->start_date->format('d M') }}</td>
                    <td>{{ $leave->end_date->format('d M') }}</td>
                    <td>{{ $leave->days }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No approved leave this month.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
