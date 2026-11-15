@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Production Line Management</h4>
        <a href="{{ route('lines.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add New Line
        </a>
    </div>

    <div class="card">
        <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive p-0">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Line Name</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Description</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Employees</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                            <th class="text-secondary opacity-7"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lines as $line)
                        <tr>
                            <td class="px-4">
                                <div class="d-flex flex-column justify-content-center">
                                    <h6 class="mb-0 text-sm">{{ $line->name }}</h6>
                                </div>
                            </td>
                            <td>
                                <p class="text-xs text-secondary mb-0">{{ $line->description ?? 'N/A' }}</p>
                            </td>
                            <td class="align-middle text-center text-sm">
                                <span class="badge bg-secondary">{{ $line->employees_count }} Assigned</span>
                            </td>
                            <td class="align-middle text-center text-sm">
                                <span class="badge bg-{{ $line->is_active ? 'success' : 'secondary' }}">
                                    {{ $line->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="align-middle text-end px-4">
                                <a href="{{ route('lines.assign', $line->id) }}" class="btn btn-sm btn-outline-info me-2">
                                    <i class="bi bi-people me-1"></i> Manage Team
                                </a>
                                @canRoute('lines.edit')
<a href="{{ route('lines.edit', $line->id) }}" class="text-secondary font-weight-bold text-xs me-3" data-bs-toggle="tooltip" title="Edit line">
                                    Edit
                                </a>
@endcanRoute
                                <!-- Add delete logic if needed -->
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
