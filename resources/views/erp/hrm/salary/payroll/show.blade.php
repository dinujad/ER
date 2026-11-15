@extends('erp.layout')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-1">{{ $payrollRun->title }}</h4>
        <p class="text-muted small mb-0">{{ $payrollRun->period_start->format('M d') }} – {{ $payrollRun->period_end->format('M d, Y') }} · <span class="badge {{ $payrollRun->status === 'finalized' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($payrollRun->status) }}</span></p>
    </div>
    <div>
        @if($payrollRun->status === 'draft')
        @canRoute('payroll-runs.review')
<form action="{{ route('payroll-runs.review', $payrollRun) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-outline-primary">Send for review</button></form>
@endcanRoute
        @elseif($payrollRun->status === 'reviewed')
        @canRoute('payroll-runs.approve-run')
<form action="{{ route('payroll-runs.approve-run', $payrollRun) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-outline-primary">Approve</button></form>
@endcanRoute
        @elseif($payrollRun->status === 'approved')
        <a href="{{ route('payroll-runs.bank-export', $payrollRun) }}" class="btn btn-outline-secondary">Bank CSV</a>
        @canRoute('payroll-runs.finalize')
<form action="{{ route('payroll-runs.finalize', $payrollRun) }}" method="POST" class="d-inline" onsubmit="return confirm('Finalize and lock this payroll?');">@csrf @method('PATCH')<button class="btn btn-success">Finalize</button></form>
@endcanRoute
        @elseif($payrollRun->status === 'finalized')
        <a href="{{ route('payroll-runs.bank-export', $payrollRun) }}" class="btn btn-outline-secondary">Bank CSV</a>
        @endif
        <a href="{{ route('payroll-runs.index') }}" class="btn btn-outline-secondary">Back to list</a>
    </div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

@if(in_array($payrollRun->status, ['draft', 'reviewed'], true) && $employeesAvailable->isNotEmpty())
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form action="{{ route('payroll-runs.add-employee', $payrollRun) }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-auto">
                <label class="form-label small mb-0">Add employee to this run</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="text" id="add-emp-search" class="form-control form-control-sm" placeholder="Search..." style="width: 140px;" autocomplete="off">
                    <select name="employee_id" id="add-emp-select" class="form-select form-select-sm" style="min-width: 180px;" required>
                        <option value="">-- Select one --</option>
                        @foreach($employeesAvailable as $e)
                            @php
                                $empLabel = $e->display_name;
                                if ($e->hikvision_person_id) { $empLabel .= ' · '.$e->hikvision_person_id; }
                                if ($e->department) { $empLabel .= ' · '.$e->department->name; }
                            @endphp
                            <option value="{{ $e->id }}" data-name="{{ strtolower($empLabel) }}">{{ $empLabel }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Leave</th>
                    <th>OT</th>
                    <th>OT pay</th>
                    <th>Work duration</th>
                    <th>Basic</th>
                    <th>Allowances</th>
                    <th>Deductions</th>
                    <th>Net salary</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($payrollRun->items as $item)
                <tr>
                    <td><a href="{{ route('employees.show', $item->employee_id) }}">{{ $item->employee?->display_name ?? '—' }}</a></td>
                    <td>{{ $item->days_present ?? 0 }}</td>
                    <td>{{ $item->days_absent ?? 0 }}</td>
                    <td>{{ number_format($item->leave_days ?? 0, 1) }}</td>
                    <td>{{ number_format(($item->ot_minutes ?? 0) / 60, 1) }}h</td>
                    <td>Rs {{ number_format($item->ot_amount ?? 0, 2) }}</td>
                    <td>{{ $item->work_duration ?? 1 }} {{ $item->work_duration_unit ?? 'Month' }}</td>
                    <td>{{ number_format($item->basic_salary, 2) }}</td>
                    <td>{{ number_format($item->total_allowances, 2) }}</td>
                    <td>{{ number_format($item->total_deductions, 2) }}</td>
                    <td><strong>{{ number_format($item->net_salary, 2) }}</strong></td>
                    <td class="text-nowrap">
                        @if($payrollRun->status === 'draft')
                            @canRoute('payroll-runs.items.edit')
<a href="{{ route('payroll-runs.items.edit', [$payrollRun, $item]) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
@endcanRoute
                        @endif
                        <a href="{{ route('payroll-runs.payslip', [$payrollRun, $item]) }}" class="btn btn-sm btn-outline-primary" target="_blank">Print paysheet</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('scripts')
<script>
(function() {
    var searchInput = document.getElementById('add-emp-search');
    var selectEl = document.getElementById('add-emp-select');
    if (!selectEl) return;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            var opts = selectEl.querySelectorAll('option[value]');
            opts.forEach(function(opt) {
                var name = (opt.getAttribute('data-name') || '').toLowerCase();
                opt.style.display = !q || name.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }
})();
</script>
@endsection
