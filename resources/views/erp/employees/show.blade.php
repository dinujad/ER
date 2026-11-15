@extends('erp.layout')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Employee Profile</h1>
        <div class="d-flex gap-2">
            @canRoute('employees.edit')
<a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> Edit</a>
@endcanRoute
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 col-lg-3">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    @if($employee->photo)
                        <img src="{{ asset('storage/' . $employee->photo) }}" alt="Photo" class="rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width: 120px; height: 120px;">
                            <i class="bi bi-person display-4 text-secondary"></i>
                        </div>
                    @endif
                    <h5 class="mb-1">{{ $employee->display_name ?: $employee->name }}</h5>
                    <p class="text-muted small mb-0">{{ $employee->nic }}</p>
                    @if($employee->department)
                        <span class="badge bg-info mt-1">{{ $employee->department->name }}</span>
                    @endif
                    @if($employee->designation)
                        <span class="badge bg-secondary mt-1">{{ $employee->designation->title }}</span>
                    @endif
                    <hr class="my-3">
                    @canRoute('employees.photo.update')
<form action="{{ route('employees.photo.update', $employee->id) }}" method="POST" enctype="multipart/form-data" class="text-start">
                        @csrf
                        <label class="form-label small mb-1">{{ $employee->photo ? 'Change photo' : 'Add photo' }}</label>
                        <div class="d-flex gap-2 align-items-end">
                            <input type="file" name="photo" class="form-control form-control-sm" accept="image/*" required>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-upload me-1"></i> Upload</button>
                        </div>
                        <small class="text-muted">Max 2MB. JPG, PNG.</small>
                    </form>
@endcanRoute
                </div>
            </div>
            <div class="card shadow mb-4">
                <div class="card-header py-2"><h6 class="mb-0">Details</h6></div>
                <div class="card-body small">
                    <p class="mb-1"><strong>Employee code:</strong> <span class="font-monospace">{{ $employee->employee_code ?: '—' }}</span></p>
                    <p class="mb-1"><strong>Hikvision ID:</strong> <span class="font-monospace">{{ $employee->hikvision_person_id ?: '—' }}</span></p>
                    <p class="mb-1"><strong>Status:</strong> {{ ucfirst($employee->status ?: 'active') }}</p>
                    <p class="mb-1"><strong>Address:</strong> {{ $employee->address ?: '—' }}</p>
                    <p class="mb-1"><strong>Emergency:</strong> {{ $employee->emergency_contact_name ?: '—' }} {{ $employee->emergency_contact_phone }}</p>
                    <p class="mb-1"><strong>ID type:</strong> {{ $employee->id_type ? str_replace('_', ' ', $employee->id_type) : '—' }}</p>
                    <p class="mb-1"><strong>Employment:</strong> {{ ucfirst($employee->employment_type ?: 'permanent') }}</p>
                    <p class="mb-1"><strong>Pay:</strong> {{ str_replace('_', ' ', $employee->pay_type ?: 'monthly') }}@if($employee->pay_rate) · Rs {{ number_format($employee->pay_rate, 2) }}@endif</p>
                    <p class="mb-1"><strong>Line:</strong> {{ $employee->productionLine?->name ?? '—' }}</p>
                    <p class="mb-1"><strong>Manager:</strong> {{ $employee->reportingManager?->display_name ?: ($employee->reportingManager?->name ?? '—') }}</p>
                    <p class="mb-1"><strong>Bank:</strong> {{ $employee->bank_name ?: '—' }} {{ $employee->bank_account_number }}</p>
                    <p class="mb-1"><strong>Payroll reg.:</strong> {{ $employee->payroll_registration_no ?: '—' }}</p>
                    <p class="mb-1"><strong>Email:</strong> {{ $employee->email ?? '—' }}</p>
                    <p class="mb-1"><strong>Contact:</strong> {{ $employee->contact ?? '—' }}</p>
                    <p class="mb-1"><strong>Gender:</strong> {{ $employee->gender ? ucfirst($employee->gender) : '—' }}</p>
                    <p class="mb-1"><strong>Birthday:</strong> {{ $employee->birthday ? $employee->birthday->format('d M Y') . ' (Age ' . \Carbon\Carbon::parse($employee->birthday)->age . ')' : '—' }}</p>
                    <p class="mb-1"><strong>Role:</strong> {{ str_replace('_', ' ', $employee->role ?? '—') }}</p>
                    <p class="mb-1"><strong>Shift:</strong> {{ $employee->defaultShift?->name ?? '—' }}</p>
                    <p class="mb-1"><strong>Join date:</strong> {{ $employee->join_date ? $employee->join_date->format('d M Y') : '—' }}</p>
                    @if($employee->hikvision_valid_from || $employee->hikvision_valid_to)
                        <p class="mb-1"><strong>Hik-Connect validity:</strong> {{ $employee->hikvision_valid_from?->format('Y-m-d H:i') ?? '—' }} to {{ $employee->hikvision_valid_to?->format('Y-m-d H:i') ?? '—' }}
                            <span class="d-block text-muted">Device access period only — not employment start/end.</span>
                        </p>
                    @endif
                    @if($employee->effective_period_start || $employee->effective_period_end)
                        <p class="mb-1"><strong>Effective period:</strong> {{ $employee->effective_period_start?->format('d M Y') ?? '—' }} to {{ $employee->effective_period_end?->format('d M Y') ?? '—' }}</p>
                    @endif
                    @if($employee->notes)
                        <p class="mb-1"><strong>Notes:</strong> {{ $employee->notes }}</p>
                    @endif
                    <p class="mb-0"><strong>Self-service:</strong> {{ $employee->self_service ? 'Enable' : 'Disable' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-8 col-lg-9">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#attendance">Attendance</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#leaves">Leaves</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#salary">Salary / Pay</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#bonuses">Bonuses</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#records">Documents & history</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="attendance">
                    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
                        <form method="GET" action="{{ route('employees.show', $employee->id) }}" class="d-flex align-items-end gap-2">
                            <div>
                                <label class="form-label small mb-1">Month</label>
                                <input type="month" name="month" class="form-control form-control-sm" value="{{ $month->format('Y-m') }}">
                            </div>
                            <button class="btn btn-sm btn-outline-primary">Show</button>
                        </form>
                        <form method="POST" action="{{ route('employees.shift.assign', $employee->id) }}" class="d-flex align-items-end gap-2">
                            @csrf
                            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                            <div>
                                <label class="form-label small mb-1">Default shift</label>
                                <select name="shift_id" class="form-select form-select-sm" style="min-width: 180px;">
                                    <option value="">No shift</option>
                                    @foreach($shifts as $shift)
                                        <option value="{{ $shift->id }}" {{ (int) $employee->shift_id === (int) $shift->id ? 'selected' : '' }}>
                                            {{ $shift->name }} · {{ \Carbon\Carbon::parse($shift->start_time)->format('h:i A') }}–{{ \Carbon\Carbon::parse($shift->end_time)->format('h:i A') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-sm btn-primary">Save shift</button>
                        </form>
                    </div>
                    @if(! $employee->defaultShift)
                        <div class="alert alert-warning py-2 small">Assign a shift to calculate overtime. OT uses the rate saved on that shift.</div>
                    @else
                        <p class="small text-muted">Shift <strong>{{ $employee->defaultShift->name }}</strong> ends {{ \Carbon\Carbon::parse($employee->defaultShift->end_time)->format('h:i A') }}. OT starts {{ (int) ($employee->defaultShift->ot_grace_mins ?? 0) }} min after that, at Rs {{ number_format($employee->defaultShift->ot_rate_per_hour ?? 0, 2) }} per hour.</p>
                    @endif
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-2"><div class="border rounded p-2"><div class="fw-bold text-success">{{ $summary['present'] }}</div><div class="small text-muted">Present</div></div></div>
                        <div class="col-6 col-md-2"><div class="border rounded p-2"><div class="fw-bold text-danger">{{ $summary['absent'] }}</div><div class="small text-muted">Absent</div></div></div>
                        <div class="col-6 col-md-2"><div class="border rounded p-2"><div class="fw-bold text-warning">{{ $summary['late'] }}</div><div class="small text-muted">Late</div></div></div>
                        <div class="col-6 col-md-2"><div class="border rounded p-2"><div class="fw-bold">{{ number_format($summary['worked_minutes'] / 60, 1) }}h</div><div class="small text-muted">Worked</div></div></div>
                        <div class="col-6 col-md-2"><div class="border rounded p-2"><div class="fw-bold">{{ number_format($summary['ot_minutes'] / 60, 1) }}h</div><div class="small text-muted">OT</div></div></div>
                        <div class="col-6 col-md-2"><div class="border rounded p-2"><div class="fw-bold">Rs {{ number_format($summary['ot_amount'], 2) }}</div><div class="small text-muted">OT pay</div></div></div>
                    </div>
                    <div class="card shadow mb-3">
                        <div class="card-header py-2"><h6 class="mb-0">Work done — {{ $month->format('F Y') }}</h6></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Worked</th><th>Shift</th><th>OT</th><th>OT pay</th><th>Status</th></tr></thead>
                                    <tbody>
                                        @php $workDays = collect($summary['days'])->whereIn('status', ['present', 'late', 'incomplete']); @endphp
                                        @forelse($workDays as $a)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($a['date'])->format('d M Y') }}</td>
                                            <td>{{ $a['first_in'] ? $a['first_in']->format('H:i') : '—' }}</td>
                                            <td>{{ $a['last_out'] ? $a['last_out']->format('H:i') : '—' }}</td>
                                            <td>{{ $a['worked_minutes'] ? number_format($a['worked_minutes'] / 60, 1).'h' : '—' }}</td>
                                            <td>{{ $a['shift_name'] ?? '—' }}</td>
                                            <td>{{ $a['ot_minutes'] ? number_format($a['ot_minutes'] / 60, 1).'h' : '—' }}</td>
                                            <td>{{ $a['ot_amount'] > 0 ? 'Rs '.number_format($a['ot_amount'], 2) : '—' }}</td>
                                            <td><span class="badge bg-{{ $a['status'] === 'present' ? 'success' : ($a['status'] === 'late' ? 'warning text-dark' : 'secondary') }}">{{ $a['status'] }}</span></td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="8" class="text-muted text-center py-3">No work records this month.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card shadow mb-4">
                        <div class="card-header py-2"><h6 class="mb-0">Absent — {{ $month->format('F Y') }}</h6></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead><tr><th>Date</th><th>Status</th></tr></thead>
                                    <tbody>
                                        @php $absentDays = collect($summary['days'])->where('status', 'absent'); @endphp
                                        @forelse($absentDays as $a)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($a['date'])->format('d M Y') }}</td>
                                            <td><span class="badge bg-danger">Absent</span></td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="2" class="text-muted text-center py-3">No absent days this month.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="leaves">
                    <div class="card shadow mb-4">
                        <div class="card-header py-2"><h6 class="mb-0">Leave History</h6></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead><tr><th>Type</th><th>Start</th><th>End</th><th>Days</th><th>Status</th></tr></thead>
                                    <tbody>
                                        @forelse($employee->leaveRequests as $lr)
                                        <tr>
                                            <td>{{ $lr->leaveType->name ?? $lr->leave_type_id }}</td>
                                            <td>{{ $lr->start_date->format('d M Y') }}</td>
                                            <td>{{ $lr->end_date->format('d M Y') }}</td>
                                            <td>{{ $lr->days }}</td>
                                            <td><span class="badge bg-{{ $lr->status === 'approved' ? 'success' : ($lr->status === 'rejected' ? 'danger' : 'warning') }}">{{ $lr->status }}</span></td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="5" class="text-muted text-center py-3">No leave requests.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="salary">
                    <div class="card shadow mb-4">
                        <div class="card-header py-2"><h6 class="mb-0">Salary / Payroll</h6></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead><tr><th>Period</th><th>Basic</th><th>Gross</th><th>Deductions</th><th>Net</th></tr></thead>
                                    <tbody>
                                        @forelse($employee->payrollRunItems as $item)
                                        <tr>
                                            <td>{{ $item->payrollRun ? $item->payrollRun->title . ' (' . \Carbon\Carbon::parse($item->payrollRun->period_start)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($item->payrollRun->period_end)->format('d M Y') . ')' : '—' }}</td>
                                            <td>{{ number_format($item->basic_salary, 2) }}</td>
                                            <td>{{ number_format($item->gross_salary, 2) }}</td>
                                            <td>{{ number_format($item->total_deductions, 2) }}</td>
                                            <td><strong>{{ number_format($item->net_salary, 2) }}</strong></td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="5" class="text-muted text-center py-3">No payroll records.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="bonuses">
                    <div class="card shadow mb-4">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Bonuses</h6>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addBonusModal"><i class="bi bi-plus"></i> Add Bonus</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead><tr><th>Type</th><th>Amount</th><th>Date</th><th>Notes</th></tr></thead>
                                    <tbody>
                                        @forelse($employee->bonuses as $b)
                                        <tr>
                                            <td>{{ ucfirst($b->type) }}</td>
                                            <td>Rs {{ number_format($b->amount, 2) }}</td>
                                            <td>{{ $b->bonus_date->format('d M Y') }}</td>
                                            <td>{{ $b->notes }}</td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="4" class="text-muted text-center py-3">No bonuses yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="records">
                    <div class="card shadow mb-3">
                        <div class="card-header py-2"><h6 class="mb-0">Documents</h6></div>
                        <ul class="list-group list-group-flush">
                            @forelse($employee->documents as $doc)
                                <li class="list-group-item"><a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank">{{ $doc->title }}</a> <span class="text-muted small">{{ $doc->original_name }}</span></li>
                            @empty
                                <li class="list-group-item text-muted">No documents. Add them from Edit.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="card shadow mb-4">
                        <div class="card-header py-2"><h6 class="mb-0">Transfers, promotions, salary changes</h6></div>
                        <ul class="list-group list-group-flush">
                            @forelse($employee->changes as $change)
                                <li class="list-group-item">
                                    <strong>{{ ucfirst($change->change_type) }}</strong>
                                    <span class="text-muted">{{ $change->effective_date->format('d M Y') }}</span>
                                    <div>{{ $change->summary }}</div>
                                    @if($change->notes)<div class="small text-muted">{{ $change->notes }}</div>@endif
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No history yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Bonus Modal -->
<div class="modal fade" id="addBonusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('employees.bonuses.store', $employee->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title">Add Bonus</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="hambuna">Hambuna</option>
                            <option value="hamadema">Hamadema</option>
                            <option value="festival">Festival</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (Rs)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="bonus_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
