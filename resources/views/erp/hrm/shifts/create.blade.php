@extends('erp.layout')

@section('content')
<div class="mb-4"><a href="{{ route('shifts.index') }}" class="text-decoration-none">Shifts</a></div>
<h4 class="mb-3">Add Shift</h4>
<div class="card border-0 shadow-sm" style="max-width: 560px;">
    <div class="card-body">
        <form method="POST" action="{{ route('shifts.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. General" required>
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label">Start time</label>
                    <input type="time" name="start_time" class="form-control" value="{{ old('start_time', '08:00') }}" required>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">End time</label>
                    <input type="time" name="end_time" class="form-control" value="{{ old('end_time', '17:00') }}" required>
                </div>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label">Grace period (min)</label>
                    <input type="number" name="grace_period_mins" class="form-control" value="{{ old('grace_period_mins', 15) }}" min="0">
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">Late after (min)</label>
                    <input type="number" name="late_threshold_mins" class="form-control" value="{{ old('late_threshold_mins', 30) }}" min="0">
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_overnight" value="1" class="form-check-input" id="overnight" {{ old('is_overnight') ? 'checked' : '' }}>
                    <label class="form-check-label" for="overnight">Overnight shift</label>
                </div>
            </div>
            <hr>
            <h6 class="mb-2">Overtime</h6>
            <p class="text-muted small">OT is the time worked after this shift ends. Pay is those hours times the rate below.</p>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label">OT starts after end (min)</label>
                    <input type="number" name="ot_grace_mins" class="form-control" value="{{ old('ot_grace_mins', 0) }}" min="0">
                    <small class="text-muted">0 = OT starts at shift end.</small>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">Workday OT (Rs / hour)</label>
                    <input type="number" name="ot_rate_per_hour" class="form-control" value="{{ old('ot_rate_per_hour', 0) }}" min="0" step="0.01">
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">Weekend OT (Rs / hour)</label>
                    <input type="number" name="weekend_ot_rate_per_hour" class="form-control" value="{{ old('weekend_ot_rate_per_hour', 0) }}" min="0" step="0.01">
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">Holiday OT (Rs / hour)</label>
                    <input type="number" name="holiday_ot_rate_per_hour" class="form-control" value="{{ old('holiday_ot_rate_per_hour', 0) }}" min="0" step="0.01">
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">Daily OT limit (hours)</label>
                    <input type="number" name="ot_daily_limit_hours" class="form-control" value="{{ old('ot_daily_limit_hours', 4) }}" min="0" step="0.5">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
