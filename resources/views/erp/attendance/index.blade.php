@extends('erp.layout')

@section('content')
<style>
    .att-card { border: none; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
    .att-card .table th { font-size: 0.75rem; text-transform: uppercase; color: #6c757d; }
    .badge-present { background: #d4edda; color: #155724; }
    .badge-absent { background: #f8d7da; color: #721c24; }
    .badge-late { background: #fff3cd; color: #856404; }
    .badge-leave { background: #cce5ff; color: #004085; }
    /* Prevent oversized pagination or icons in this page */
    .content .card-footer .pagination { font-size: 0.875rem; }
    .content .card-footer .pagination .page-link { padding: 0.375rem 0.75rem; }
    .content .alert .bi, .content .card-footer .bi { font-size: 1em !important; max-width: 1.25em; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h4 class="mb-0">Daily Attendance</h4>
    <div class="d-flex gap-2">
        @canRoute('attendance.manual')
<a href="{{ route('attendance.manual') }}?date={{ request('date', now()->toDateString()) }}" class="btn btn-success"><i class="bi bi-pencil-square me-1"></i>Mark Attendance</a>
@endcanRoute

        <a href="{{ route('attendance.hik-connect-import.create') }}" class="btn btn-primary"><i class="bi bi-fingerprint me-1"></i>Import Hik-Connect Attendance</a>
    </div>
</div>

<form method="GET" action="{{ route('attendance.index') }}" class="card att-card mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date', now()->toDateString()) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Employee</label>
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($employees as $e)
                    <option value="{{ $e->id }}" {{ request('employee_id') == $e->id ? 'selected' : '' }}>{{ $e->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Department</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($departments as $d)
                    <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Present</option>
                    <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>Absent</option>
                    <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>Late</option>
                    <option value="leave" {{ request('status') === 'leave' ? 'selected' : '' }}>Leave</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary btn-sm w-100">Filter</button>
            </div>
        </div>
    </div>
</form>

<div class="card att-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Emp ID</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Worked</th>
                        <th>Late</th>
                        <th>OT</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $a)
                    <tr>
                        <td><span class="text-muted fw-medium font-monospace">{{ $a->employee?->hikvision_person_id ?: ($a->employee?->device_enroll_id ?? '—') }}</span></td>
                        <td>
                            <strong>{{ $a->employee->name ?? '—' }}</strong>
                            @if($a->hikvision_status)
                            <br><small class="text-muted">{{ $a->hikvision_status }}</small>
                            @endif
                        </td>
                        <td>{{ $a->employee->department->name ?? '—' }}</td>
                        <td>{{ $a->date ? $a->date->format('M d, Y') : '—' }}</td>
                        <td>{{ $a->first_in ? $a->first_in->timezone(config('app.timezone'))->format('Y-m-d H:i') : '—' }}</td>
                        <td>
                            @if($a->last_out)
                                {{ $a->last_out->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                            @elseif($a->first_in)
                                <span class="text-warning">Missing checkout</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $a->worked_minutes === null ? '—' : intdiv((int) $a->worked_minutes, 60) . 'h ' . str_pad((string) ((int) $a->worked_minutes % 60), 2, '0', STR_PAD_LEFT) . 'm' }}</td>
                        <td>{{ $a->late_minutes === null ? '—' : ((int) $a->late_minutes) . 'm' }}</td>
                        <td>{{ $a->overtime_minutes === null ? '—' : ((int) $a->overtime_minutes) . 'm' }}</td>
                        <td>
                            @if($a->status === 'present')<span class="badge badge-present">Present</span>
                            @elseif($a->status === 'late')<span class="badge badge-late">Late</span>
                            @elseif($a->status === 'absent')<span class="badge badge-absent">Absent</span>
                            @elseif($a->status === 'leave')<span class="badge badge-leave">Leave</span>
                            @elseif($a->status === 'incomplete')<span class="badge bg-warning text-dark">Incomplete</span>
                            @else<span class="badge bg-secondary">{{ $a->status ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            No attendance records. @canRoute('attendance.manual')
<a href="{{ route('attendance.manual') }}">Mark attendance</a>
@endcanRoute
 or <a href="{{ route('attendance.hik-connect-import.create') }}">import Hik-Connect attendance</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0">{{ $attendances->links() }}</div>
    </div>
</div>
@endsection
