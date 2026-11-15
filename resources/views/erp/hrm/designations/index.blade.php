@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Designations</h4>
    <a href="{{ route('designations.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Designation</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Title</th><th>Department</th><th>Level</th><th>Employees</th><th width="120"></th></tr></thead>
            <tbody>
                @forelse($designations as $d)
                <tr>
                    <td>{{ $d->title }}</td>
                    <td>{{ $d->department->name ?? '—' }}</td>
                    <td>{{ $d->level ?? '—' }}</td>
                    <td>{{ $d->employees_count }}</td>
                    <td>
                        @canRoute('designations.edit')
<a href="{{ route('designations.edit', $d) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
@endcanRoute
                        @canRoute('designations.destroy')
<form action="{{ route('designations.destroy', $d) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?');">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
@endcanRoute
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No designations. <a href="{{ route('designations.create') }}">Add one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $designations->links() }}</div>
</div>
@endsection
