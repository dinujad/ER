@extends('erp.layout')

@section('content')
<style>
    .badge-pending { background: #fff3cd; color: #856404; }
    .badge-approved { background: #d4edda; color: #155724; }
    .badge-rejected { background: #f8d7da; color: #721c24; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h4 class="mb-0">Leave Requests</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('leave-requests.balances') }}" class="btn btn-outline-secondary">Balances</a>
        <a href="{{ route('leave-requests.calendar') }}" class="btn btn-outline-secondary">Calendar</a>
        <a href="{{ route('leave-requests.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Apply Leave</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<form method="GET" action="{{ route('leave-requests.index') }}" class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
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
                <label class="form-label small mb-0">Leave type</label>
                <select name="leave_type_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($leaveTypes as $lt)
                    <option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary btn-sm w-100">Filter</button>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Leave type</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Days</th>
                    <th>Status</th>
                    <th>Approved by</th>
                    <th width="140"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaveRequests as $lr)
                <tr>
                    <td>{{ $lr->employee->name ?? '—' }}</td>
                    <td>{{ $lr->leaveType->name ?? '—' }}</td>
                    <td>{{ $lr->start_date->format('M d, Y') }}</td>
                    <td>{{ $lr->end_date->format('M d, Y') }}</td>
                    <td>{{ $lr->days }} <span class="text-muted">{{ $lr->duration_unit ?? 'full' }}</span></td>
                    <td>
                        @if($lr->status === 'pending')<span class="badge badge-pending">Pending</span>
                        @elseif($lr->status === 'approved')<span class="badge badge-approved">Approved</span>
                        @else<span class="badge badge-rejected">Rejected</span>
                        @endif
                    </td>
                    <td>{{ $lr->approver?->name ?? '—' }}</td>
                    <td>
                        @if($lr->status === 'pending' && ($lr->supervisor_status ?? 'pending') !== 'approved')
                        @canRoute('leave-requests.supervisor')
<form action="{{ route('leave-requests.supervisor', $lr) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button type="submit" class="btn btn-sm btn-outline-primary">Supervisor</button></form>
@endcanRoute
                        @endif
                        @if($lr->status === 'pending' && ($lr->supervisor_status ?? '') === 'approved')
                        @canRoute('leave-requests.approve')
<form action="{{ route('leave-requests.approve', $lr) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button type="submit" class="btn btn-sm btn-success">HR approve</button></form>
@endcanRoute
                        @endif
                        @if(in_array($lr->status, ['pending', 'approved'], true))
                        @canRoute('leave-requests.cancel')
<form action="{{ route('leave-requests.cancel', $lr) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button type="submit" class="btn btn-sm btn-outline-secondary">Cancel</button></form>
@endcanRoute
                        @endif
                        @if($lr->document_path)
                        <a href="{{ asset('storage/'.$lr->document_path) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Doc</a>
                        @endif
                        @if($lr->status === 'pending')
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $lr->id }}">Reject</button>
                        <div class="modal fade" id="rejectModal{{ $lr->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    @canRoute('leave-requests.reject')
<form method="POST" action="{{ route('leave-requests.reject', $lr) }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="modal-header"><h5 class="modal-title">Reject leave</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <label class="form-label">Reason (optional)</label>
                                            <textarea name="rejection_reason" class="form-control" rows="2"></textarea>
                                        </div>
                                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Reject</button></div>
                                    </form>
@endcanRoute
                                </div>
                            </div>
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No leave requests. <a href="{{ route('leave-requests.create') }}">Apply leave</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $leaveRequests->links() }}</div>
</div>
@endsection
