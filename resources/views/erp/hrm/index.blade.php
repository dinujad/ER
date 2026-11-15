@extends('erp.layout')

@section('content')
<style>
    .hrm-hero {
        background: linear-gradient(135deg, #1e3a5f 0%, #0d2137 100%);
        color: #fff;
        border-radius: 16px;
        padding: 1.35rem 1.5rem;
        margin-bottom: 1.25rem;
    }
    .hrm-kpis { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
    .hrm-kpi-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 0.95rem 1rem;
        text-decoration: none;
        color: inherit;
        min-height: 92px;
    }
    .hrm-kpi-card:hover { border-color: #c5d2e4; color: inherit; }
    .hrm-kpi-num { font-size: 1.65rem; font-weight: 700; line-height: 1.1; color: #1e3a5f; }
    .hrm-kpi-label { margin-top: 0.25rem; font-size: 0.72rem; font-weight: 650; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; }
    .hrm-kpi-num.ok { color: #157347; }
    .hrm-kpi-num.bad { color: #b42318; }
    .hrm-kpi-num.warn { color: #b54708; }
    .badge-present { background: #e8f6ee; color: #157347; }
    .badge-absent { background: #fdecec; color: #b42318; }
    .badge-late { background: #fff6e8; color: #b54708; }
    @media (max-width: 1100px) { .hrm-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 640px) { .hrm-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="hrm-hero d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="h4 mb-1 fw-bold">HR</h1>
        <p class="mb-0 small" style="opacity:.85;">{{ $attendanceOn->isToday() ? 'Today' : $attendanceOn->format('l, d M Y') }} · employees, attendance, and payroll in one place</p>
    </div>
    <div class="d-flex gap-2">
        @canRoute('attendance.manual')
<a href="{{ route('attendance.manual', ['date' => $attendanceDate]) }}" class="btn btn-light btn-sm">Mark attendance</a>
@endcanRoute

        <a href="{{ route('employees.index') }}" class="btn btn-outline-light btn-sm">Employees</a>
    </div>
</div>

<div class="hrm-kpis">
    <a class="hrm-kpi-card" href="{{ route('employees.index') }}">
        <div class="hrm-kpi-num">{{ $totalEmployees }}</div>
        <div class="hrm-kpi-label">Employees</div>
    </a>
    <a class="hrm-kpi-card" href="{{ route('attendance.index', ['date' => $attendanceDate]) }}">
        <div class="hrm-kpi-num ok">{{ $presentToday }}</div>
        <div class="hrm-kpi-label">Present</div>
    </a>
    <a class="hrm-kpi-card" href="{{ route('attendance.index', ['date' => $attendanceDate, 'status' => 'absent']) }}">
        <div class="hrm-kpi-num bad">{{ $absentToday }}</div>
        <div class="hrm-kpi-label">Absent</div>
    </a>
    <a class="hrm-kpi-card" href="{{ route('attendance.index', ['date' => $attendanceDate, 'status' => 'late']) }}">
        <div class="hrm-kpi-num warn">{{ $lateToday }}</div>
        <div class="hrm-kpi-label">Late</div>
    </a>
    <a class="hrm-kpi-card" href="{{ route('leave-requests.index') }}">
        <div class="hrm-kpi-num">{{ $pendingLeaveCount ?? 0 }}</div>
        <div class="hrm-kpi-label">Leave waiting</div>
    </a>
    <a class="hrm-kpi-card" href="{{ route('leave-requests.calendar') }}">
        <div class="hrm-kpi-num">{{ $onLeaveToday ?? 0 }}</div>
        <div class="hrm-kpi-label">On leave</div>
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Attendance · {{ $attendanceOn->format('d M Y') }}</h6>
                <a href="{{ route('attendance.index', ['date' => $attendanceDate]) }}" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
            <div class="table-responsive">
                @if($todayRows->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">No attendance for this day. @canRoute('attendance.manual')
<a href="{{ route('attendance.manual', ['date' => $attendanceDate]) }}">Mark attendance</a>
@endcanRoute
.</p>
                @else
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>In</th>
                                <th>Out</th>
                                <th>Worked</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todayRows->take(12) as $r)
                            <tr>
                                <td>@if($r->employee)<a href="{{ route('employees.show', $r->employee_id) }}">{{ $r->employee->display_name ?: $r->employee->name }}</a>@else — @endif</td>
                                <td>{{ $r->first_in ? $r->first_in->format('h:i A') : '—' }}</td>
                                <td>{{ $r->last_out ? $r->last_out->format('h:i A') : '—' }}</td>
                                <td>{{ $r->worked_minutes ? round($r->worked_minutes / 60, 1).'h' : '—' }}</td>
                                <td>
                                    @if($r->status === 'present')<span class="badge badge-present">Present</span>
                                    @elseif($r->status === 'late')<span class="badge badge-late">Late</span>
                                    @elseif($r->status === 'absent')<span class="badge badge-absent">Absent</span>
                                    @else<span class="badge bg-light text-dark">{{ $r->status ?? '—' }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header"><h6 class="mb-0">Payroll</h6></div>
            <div class="card-body">
                @if(isset($lastPayrollRun) && $lastPayrollRun)
                    <p class="mb-1 fw-semibold">{{ $lastPayrollRun->period_start?->format('d M') }} – {{ $lastPayrollRun->period_end?->format('d M Y') }}</p>
                    <p class="small text-muted mb-3">{{ ucfirst($lastPayrollRun->status) }} · {{ $lastPayrollRun->created_at->diffForHumans() }}</p>
                    <a href="{{ route('payroll-runs.show', $lastPayrollRun) }}" class="btn btn-sm btn-outline-primary">Open payroll</a>
                @else
                    <p class="text-muted small mb-3">No payroll yet.</p>
                    <a href="{{ route('payroll-runs.create') }}" class="btn btn-sm btn-outline-primary">Create payroll</a>
                @endif
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Notices</h6>
                <a href="{{ route('announcements.index') }}" class="small">All</a>
            </div>
            <div class="card-body">
                @if(isset($announcements) && $announcements->isNotEmpty())
                    <ul class="list-unstyled mb-0">
                        @foreach($announcements as $a)
                            <li class="pb-2 mb-2 border-bottom">
                                <div class="fw-semibold">{{ $a->title }}</div>
                                @if($a->body)<div class="small text-muted">{{ Str::limit($a->body, 90) }}</div>@endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted small mb-0">No notices.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
