@extends('erp.layout')

@section('content')
<style>
    .att-card { border: none; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
    .att-card .table th { font-size: 0.75rem; text-transform: uppercase; color: #6c757d; }
    .manual-date-box { background: #f8f9fa; border-radius: 10px; padding: 1rem 1.25rem; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h4 class="mb-0">Mark Attendance Manually</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Attendance</a>
        @if(!$employees->isEmpty())
        <button type="submit" form="manual-att-form" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save Attendance</button>
        @endif
    </div>
</div>

@canRoute('attendance.manual')
<form method="GET" action="{{ route('attendance.manual') }}" class="card att-card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-0 fw-semibold">Select Date</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $d)
                    <option value="{{ $d->id }}" {{ ($departmentId ?? '') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Load</button>
            </div>
        </div>
    </div>
</form>
@endcanRoute


@canRoute('attendance.manual.store')
<form method="POST" action="{{ route('attendance.manual.store') }}" id="manual-att-form">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">

    <div class="card att-card mb-3">
        <div class="card-body">
            <div class="row align-items-center g-2 flex-wrap">
                <div class="col-auto">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-calendar3 me-1"></i> Date: <strong>{{ \Carbon\Carbon::parse($date)->format('l, d M Y') }}</strong>
                        &nbsp;|&nbsp; <strong>{{ $employees->count() }}</strong> employee(s). Fill check-in, check-out and status for each, then click Save.
                    </p>
                </div>
                @if(!$employees->isEmpty())
                <div class="col-auto ms-md-auto">
                    <label class="form-label small mb-0 me-1">Search by Emp ID or Name</label>
                    <input type="text" id="att-emp-search" class="form-control form-control-sm" placeholder="e.g. 5 or ajantha" style="max-width: 200px;">
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card att-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="att-manual-table">
                    <thead class="table-light">
                        <tr>
                            <th>Emp ID</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $index => $emp)
                        @php
                            $rec = $existing->get($emp->id);
                        @endphp
                        @php
                            $eid = $emp->device_enroll_id ?? '';
                            $showEid = $eid !== '' ? $eid : '—';
                        @endphp
                        <tr class="att-emp-row" data-emp-id="{{ $emp->id }}" data-emp-enroll-id="{{ $eid }}" data-emp-name="{{ strtolower($emp->name ?? '') }}">
                            <td><span class="text-muted fw-medium">{{ $showEid }}</span></td>
                            <td>
                                <strong>{{ $emp->name }}</strong>
                                <input type="hidden" name="rows[{{ $index }}][employee_id]" value="{{ $emp->id }}">
                            </td>
                            <td>{{ $emp->department->name ?? '—' }}</td>
                            <td>
                                <input type="time" name="rows[{{ $index }}][check_in]" class="form-control form-control-sm" style="max-width: 120px;"
                                    value="{{ $rec && $rec->first_in ? $rec->first_in->format('H:i') : '' }}" placeholder="09:00">
                            </td>
                            <td>
                                <input type="time" name="rows[{{ $index }}][check_out]" class="form-control form-control-sm" style="max-width: 120px;"
                                    value="{{ $rec && $rec->last_out ? $rec->last_out->format('H:i') : '' }}" placeholder="17:00">
                            </td>
                            <td>
                                <select name="rows[{{ $index }}][status]" class="form-select form-select-sm" style="max-width: 140px;">
                                    <option value="present" {{ ($rec && $rec->status === 'present') ? 'selected' : '' }}>Present</option>
                                    <option value="absent" {{ ($rec && $rec->status === 'absent') ? 'selected' : '' }}>Absent</option>
                                    <option value="late" {{ ($rec && $rec->status === 'late') ? 'selected' : '' }}>Late</option>
                                    <option value="leave" {{ ($rec && $rec->status === 'leave') ? 'selected' : '' }}>Leave</option>
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($employees->isEmpty())
            <div class="text-center text-muted py-4">
                No employees found. @canRoute('attendance.manual')
<a href="{{ route('attendance.manual') }}">Clear department filter</a>
@endcanRoute
 or add employees in HRM.
            </div>
            @endif
        </div>
    </div>
</form>
@endcanRoute


@if(!$employees->isEmpty())
<script>
(function() {
    var searchEl = document.getElementById('att-emp-search');
    var rows = document.querySelectorAll('#att-manual-table .att-emp-row');
    if (!searchEl || !rows.length) return;
    searchEl.addEventListener('input', function() {
        var q = (this.value || '').trim().toLowerCase();
        rows.forEach(function(tr) {
            var enrollId = (tr.getAttribute('data-emp-enroll-id') || '').toString().toLowerCase();
            var name = tr.getAttribute('data-emp-name') || '';
            var show = !q || enrollId.indexOf(q) !== -1 || name.indexOf(q) !== -1;
            tr.style.display = show ? '' : 'none';
        });
    });
})();
</script>
@endif
@endsection
