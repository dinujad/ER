@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Leave Types</h4>
    <a href="{{ route('leave-types.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Leave Type</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Name</th><th>Code</th><th>Days/Year</th><th>Paid</th><th>Approval</th><th>Active</th><th width="120"></th></tr>
            </thead>
            <tbody>
                @forelse($leaveTypes as $lt)
                <tr>
                    <td>{{ $lt->name }}</td>
                    <td>{{ $lt->code ?? '—' }}</td>
                    <td>{{ $lt->days_per_year ?: '—' }}</td>
                    <td>{{ $lt->is_paid ? 'Yes' : 'No' }}</td>
                    <td>{{ $lt->requires_approval ? 'Yes' : 'No' }}</td>
                    <td><span class="badge {{ $lt->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $lt->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>
                        @canRoute('leave-types.edit')
<a href="{{ route('leave-types.edit', $lt) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
@endcanRoute
                        @canRoute('leave-types.destroy')
<form action="{{ route('leave-types.destroy', $lt) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this leave type?');">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
@endcanRoute
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No leave types. <a href="{{ route('leave-types.create') }}">Add one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $leaveTypes->links() }}</div>
</div>
@endsection
