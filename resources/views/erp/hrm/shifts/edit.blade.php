@extends('erp.layout')

@section('content')
<div class="mb-4"><a href="{{ route('shifts.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Shifts</a></div>
<h4 class="mb-3">Edit Shift</h4>
<div class="card border-0 shadow-sm" style="max-width: 560px;">
    <div class="card-body">
        @canRoute('shifts.update')
<form method="POST" action="{{ route('shifts.update', $shift) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $shift->name) }}" required>
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label">Start time</label>
                    <input type="time" name="start_time" class="form-control" value="{{ old('start_time', \Carbon\Carbon::parse($shift->start_time)->format('H:i')) }}" required>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">End time</label>
                    <input type="time" name="end_time" class="form-control" value="{{ old('end_time', \Carbon\Carbon::parse($shift->end_time)->format('H:i')) }}" required>
                </div>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label">Grace period (min)</label>
                    <input type="number" name="grace_period_mins" class="form-control" value="{{ old('grace_period_mins', $shift->grace_period_mins ?? 15) }}" min="0">
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">Late after (min)</label>
                    <input type="number" name="late_threshold_mins" class="form-control" value="{{ old('late_threshold_mins', $shift->late_threshold_mins ?? 30) }}" min="0">
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_overnight" value="1" class="form-check-input" id="overnight" {{ old('is_overnight', $shift->is_overnight) ? 'checked' : '' }}>
                    <label class="form-check-label" for="overnight">Overnight shift</label>
                </div>
            </div>
            <hr>
            <h6 class="mb-2">Overtime</h6>
            <p class="text-muted small">Example: shift ends 5:00 PM, rate Rs 500, clock-out 7:30 PM = 2.5 hours × 500.</p>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label">OT starts after end (min)</label>
                    <input type="number" name="ot_grace_mins" class="form-control" value="{{ old('ot_grace_mins', $shift->ot_grace_mins ?? 0) }}" min="0">
                    <small class="text-muted">0 = OT starts at shift end.</small>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">OT rate (Rs / hour)</label>
                    <input type="number" name="ot_rate_per_hour" class="form-control" value="{{ old('ot_rate_per_hour', $shift->ot_rate_per_hour ?? 0) }}" min="0" step="0.01">
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">Weekend OT (Rs / hour)</label>
                    <input type="number" name="weekend_ot_rate_per_hour" class="form-control" value="{{ old('weekend_ot_rate_per_hour', $shift->weekend_ot_rate_per_hour ?? 0) }}" min="0" step="0.01">
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">Holiday OT (Rs / hour)</label>
                    <input type="number" name="holiday_ot_rate_per_hour" class="form-control" value="{{ old('holiday_ot_rate_per_hour', $shift->holiday_ot_rate_per_hour ?? 0) }}" min="0" step="0.01">
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">Daily OT limit (hours)</label>
                    <input type="number" name="ot_daily_limit_hours" class="form-control" value="{{ old('ot_daily_limit_hours', $shift->ot_daily_limit_hours ?? 4) }}" min="0" step="0.5">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
@endcanRoute
    </div>
</div>
@endsection
