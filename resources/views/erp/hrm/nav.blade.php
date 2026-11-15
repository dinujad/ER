<style>
    .hr-menu {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.85rem 1.25rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 0.95rem 1.1rem 0.8rem;
        margin: 0 0 1.25rem;
    }
    .hr-menu-label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #7b8ba3;
        margin-bottom: 0.35rem;
    }
    .hr-menu a {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.32rem 0.45rem;
        margin: 0 -0.45rem;
        border-radius: 8px;
        color: #1e3a5f;
        text-decoration: none;
        font-size: 0.86rem;
        font-weight: 550;
        line-height: 1.2;
    }
    .hr-menu a i { width: 1rem; text-align: center; color: #5b7190; font-size: 0.95rem; }
    .hr-menu a:hover { background: #f4f7fb; color: #1e3a5f; }
    .hr-menu a.is-active { background: #1e3a5f; color: #fff; }
    .hr-menu a.is-active i { color: #fff; }
    @media (max-width: 1200px) {
        .hr-menu { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 720px) {
        .hr-menu { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
</style>
<nav class="hr-menu" aria-label="HR">
    <div>
        <div class="hr-menu-label">People</div>
        <a class="{{ request()->routeIs('hrm.index') ? 'is-active' : '' }}" href="{{ route('hrm.index') }}"><i class="bi bi-grid-1x2"></i>Home</a>
        <a class="{{ request()->routeIs('employees.*') && ! request()->routeIs('employees.hik-connect-import.*') ? 'is-active' : '' }}" href="{{ route('employees.index') }}"><i class="bi bi-people"></i>Employees</a>
        <a class="{{ request()->routeIs('departments.*') ? 'is-active' : '' }}" href="{{ route('departments.index') }}"><i class="bi bi-building"></i>Departments</a>
        <a class="{{ request()->routeIs('designations.*') ? 'is-active' : '' }}" href="{{ route('designations.index') }}"><i class="bi bi-person-badge"></i>Designations</a>
    </div>
    <div>
        <div class="hr-menu-label">Time</div>
        <a class="{{ request()->routeIs('attendance.index', 'attendance.manual', 'attendance.manual.store') ? 'is-active' : '' }}" href="{{ route('attendance.index') }}"><i class="bi bi-calendar3"></i>Attendance</a>
        <a class="{{ request()->routeIs('shifts.*') ? 'is-active' : '' }}" href="{{ route('shifts.index') }}"><i class="bi bi-clock"></i>Shifts</a>
        <a class="{{ request()->routeIs('roster.*') ? 'is-active' : '' }}" href="{{ route('roster.index') }}"><i class="bi bi-calendar-week"></i>Roster</a>
        <a class="{{ request()->routeIs('leave-requests.*') ? 'is-active' : '' }}" href="{{ route('leave-requests.index') }}"><i class="bi bi-calendar-x"></i>Leave</a>
        <a class="{{ request()->routeIs('leave-types.*') ? 'is-active' : '' }}" href="{{ route('leave-types.index') }}"><i class="bi bi-tag"></i>Leave types</a>
        <a class="{{ request()->routeIs('ot-requests.*') ? 'is-active' : '' }}" href="{{ route('ot-requests.index') }}"><i class="bi bi-hourglass-split"></i>Overtime</a>
        @if(auth()->user()?->can_access('hrm.attendance'))
        <a class="{{ request()->routeIs('attendance.hik-connect-import.*') ? 'is-active' : '' }}" href="{{ route('attendance.hik-connect-import.create') }}"><i class="bi bi-fingerprint"></i>Import punches</a>
        @endif
        @if(auth()->user()?->can_access('employees.create'))
        <a class="{{ request()->routeIs('employees.hik-connect-import.*') ? 'is-active' : '' }}" href="{{ route('employees.hik-connect-import.create') }}"><i class="bi bi-person-plus"></i>Import employees</a>
        @endif
    </div>
    <div>
        <div class="hr-menu-label">Pay</div>
        <a class="{{ request()->routeIs('payroll-runs.*', 'employee-salaries.*', 'salary-components.*') ? 'is-active' : '' }}" href="{{ route('payroll-runs.index') }}"><i class="bi bi-cash-stack"></i>Payroll</a>
        <a class="{{ request()->routeIs('hr.incentives.*') ? 'is-active' : '' }}" href="{{ route('hr.incentives.index') }}"><i class="bi bi-coin"></i>Incentives</a>
        <a class="{{ request()->routeIs('hr.reports') ? 'is-active' : '' }}" href="{{ route('hr.reports') }}"><i class="bi bi-bar-chart"></i>Reports</a>
        <a class="{{ request()->routeIs('hr.self') ? 'is-active' : '' }}" href="{{ route('hr.self') }}"><i class="bi bi-person-circle"></i>My HR</a>
    </div>
    <div>
        <div class="hr-menu-label">Factory</div>
        <a class="{{ request()->routeIs('hr.skills.*') ? 'is-active' : '' }}" href="{{ route('hr.skills.index') }}"><i class="bi bi-stars"></i>Skills</a>
        <a class="{{ request()->routeIs('hr.training.*') ? 'is-active' : '' }}" href="{{ route('hr.training.index') }}"><i class="bi bi-mortarboard"></i>Training</a>
        <a class="{{ request()->routeIs('hr.manpower.*') ? 'is-active' : '' }}" href="{{ route('hr.manpower.index') }}"><i class="bi bi-diagram-3"></i>Manpower</a>
        <a class="{{ request()->routeIs('hr.recruitment.*') ? 'is-active' : '' }}" href="{{ route('hr.recruitment.index') }}"><i class="bi bi-person-lines-fill"></i>Recruitment</a>
        <a class="{{ request()->routeIs('hr.performance.*') ? 'is-active' : '' }}" href="{{ route('hr.performance.index') }}"><i class="bi bi-clipboard-check"></i>Performance</a>
    </div>
    <div>
        <div class="hr-menu-label">Care</div>
        <a class="{{ request()->routeIs('hr.welfare.*') ? 'is-active' : '' }}" href="{{ route('hr.welfare.index') }}"><i class="bi bi-heart"></i>Welfare</a>
        <a class="{{ request()->routeIs('hr.safety.*') ? 'is-active' : '' }}" href="{{ route('hr.safety.index') }}"><i class="bi bi-shield-check"></i>Safety</a>
        <a class="{{ request()->routeIs('hr.grievances.*') ? 'is-active' : '' }}" href="{{ route('hr.grievances.index') }}"><i class="bi bi-chat-left-text"></i>Discipline</a>
        <a class="{{ request()->routeIs('hr.letters.*') ? 'is-active' : '' }}" href="{{ route('hr.letters.index') }}"><i class="bi bi-file-earmark-text"></i>Letters</a>
        <a class="{{ request()->routeIs('hr.resignations.*') ? 'is-active' : '' }}" href="{{ route('hr.resignations.index') }}"><i class="bi bi-box-arrow-right"></i>Offboarding</a>
        <a class="{{ request()->routeIs('hr.settings', 'announcements.*') ? 'is-active' : '' }}" href="{{ route('hr.settings') }}"><i class="bi bi-gear"></i>Settings</a>
    </div>
</nav>
