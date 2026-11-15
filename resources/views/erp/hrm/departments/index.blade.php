@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Departments</h4>
    <a href="{{ route('departments.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Department</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Name</th><th>Code</th><th>Employees</th><th width="120"></th></tr></thead>
            <tbody>
                @forelse($departments as $d)
                <tr>
                    <td>{{ $d->name }}</td>
                    <td>{{ $d->code ?? '—' }}</td>
                    <td>{{ $d->employees_count }}</td>
                    <td>
                        @canRoute('departments.edit')
<a href="{{ route('departments.edit', $d) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
@endcanRoute
                        @canRoute('departments.destroy')
<form action="{{ route('departments.destroy', $d) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this department?');">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
@endcanRoute
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No departments. <a href="{{ route('departments.create') }}">Add one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $departments->links() }}</div>
</div>
@endsection
