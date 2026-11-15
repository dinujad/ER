@extends('erp.layout')

@section('content')
<div class="mb-4"><a href="{{ route('leave-requests.index') }}" class="text-decoration-none">Leave Requests</a></div>
<h4 class="mb-3">Apply for Leave</h4>
<div class="card border-0 shadow-sm" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('leave-requests.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label">Employee *</label>
                <select name="employee_id" class="form-select" required>
                    <option value="">Select employee</option>
                    @foreach($employees as $e)
                    <option value="{{ $e->id }}" {{ old('employee_id') == $e->id ? 'selected' : '' }}>{{ $e->name }}</option>
                    @endforeach
                </select>
                @error('employee_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Leave type *</label>
                <select name="leave_type_id" class="form-select" required>
                    <option value="">Select type</option>
                    @foreach($leaveTypes as $lt)
                    <option value="{{ $lt->id }}" {{ old('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }} · {{ $lt->is_paid ? 'Paid' : 'Unpaid' }}@if($lt->days_per_year) · {{ $lt->days_per_year }} + {{ $lt->carry_forward_days ?? 0 }} carry @endif</option>
                    @endforeach
                </select>
                @error('leave_type_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label">Start date *</label>
                    <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                    @error('start_date')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">End date *</label>
                    <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
                    @error('end_date')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label">Duration</label>
                    <select name="duration_unit" class="form-select" required>
                        <option value="full" {{ old('duration_unit', 'full') === 'full' ? 'selected' : '' }}>Full day</option>
                        <option value="half" {{ old('duration_unit') === 'half' ? 'selected' : '' }}>Half day</option>
                        <option value="hourly" {{ old('duration_unit') === 'hourly' ? 'selected' : '' }}>Hourly</option>
                    </select>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">Hours (hourly only)</label>
                    <input type="number" name="hours" class="form-control" min="0.5" max="12" step="0.5" value="{{ old('hours') }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Supporting document</label>
                <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            </div>
            <div class="mb-3">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" rows="2">{{ old('reason') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit request</button>
            <a href="{{ route('leave-requests.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
