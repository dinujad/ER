@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Shifts</h4>
    <a href="{{ route('shifts.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Shift</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Name</th><th>Start</th><th>End</th><th>Grace</th><th>Late after</th><th>OT after end</th><th>OT rate / hour</th><th width="120"></th></tr>
            </thead>
            <tbody>
                @forelse($shifts as $s)
                <tr>
                    <td>{{ $s->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($s->start_time)->format('h:i A') }}</td>
                    <td>{{ \Carbon\Carbon::parse($s->end_time)->format('h:i A') }}</td>
                    <td>{{ $s->grace_period_mins ?? 15 }} min</td>
                    <td>{{ $s->late_threshold_mins ?? 30 }} min</td>
                    <td>{{ $s->ot_grace_mins ?? 0 }} min</td>
                    <td>Rs {{ number_format($s->ot_rate_per_hour ?? 0, 2) }}</td>
                    <td>
                        @canRoute('shifts.edit')
<a href="{{ route('shifts.edit', $s) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
@endcanRoute
                        @canRoute('shifts.destroy')
<form action="{{ route('shifts.destroy', $s) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?');">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
@endcanRoute
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No shifts. <a href="{{ route('shifts.create') }}">Add one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $shifts->links() }}</div>
</div>
@endsection
