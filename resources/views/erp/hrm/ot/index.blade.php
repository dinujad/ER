@extends('erp.layout')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h4 class="mb-0">Overtime</h4>
    <a href="{{ route('ot-requests.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Request OT</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee</th><th>Date</th><th>Type</th><th>Planned</th><th>Actual attendance</th><th>Rate</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $row)
                <tr>
                    <td>{{ $row->employee?->display_name ?: $row->employee?->name }}<div class="small text-muted">{{ $row->employee?->department?->name }}</div></td>
                    <td>{{ $row->work_date->format('d M Y') }}</td>
                    <td>{{ ucfirst($row->category) }}</td>
                    <td>{{ $row->planned_hours }}h @if($row->over_limit)<span class="badge bg-warning text-dark">Over limit</span>@endif</td>
                    <td>{{ number_format(($row->actual_ot_minutes ?? $row->actual_minutes) / 60, 1) }}h</td>
                    <td>Rs {{ number_format($row->rate_per_hour, 2) }}</td>
                    <td>{{ ucfirst($row->status) }}@if($row->status === 'pending' && $row->supervisor_status === 'approved') <span class="text-muted small">HR</span>@endif</td>
                    <td class="text-nowrap">
                        @if($row->status === 'pending' && $row->supervisor_status !== 'approved')
                        @canRoute('ot-requests.supervisor')
<form action="{{ route('ot-requests.supervisor', $row) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary">Supervisor</button></form>
@endcanRoute
                        @endif
                        @if($row->status === 'pending' && $row->supervisor_status === 'approved')
                        @canRoute('ot-requests.approve')
<form action="{{ route('ot-requests.approve', $row) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-success">HR approve</button></form>
@endcanRoute
                        @endif
                        @if($row->status === 'pending')
                        @canRoute('ot-requests.reject')
<form action="{{ route('ot-requests.reject', $row) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger">Reject</button></form>
@endcanRoute
                        @endif
                        @if($row->status === 'approved')<span class="small">Rs {{ number_format($row->amount, 2) }}</span>@endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No OT requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $requests->links() }}</div>
</div>
@endsection
