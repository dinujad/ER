@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0 text-gray-800">Vehicles</h1>
        <a href="{{ route('vehicles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Vehicle</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="alert alert-light border small mb-3">
        <strong>දිනයට driver assign:</strong> එක් vehicle එකට දිනයකට driver එක් කෙනෙක්. Vehicle <strong>Edit</strong> → <strong>Daily driver assignment</strong> section එකෙන් date + driver select කරන්න.
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Number Plate</th>
                            <th>Type</th>
                            <th>Today's driver</th>
                            <th>Default driver</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehicles as $v)
                            @php
                                $todayAssignment = $v->driverAssignments->first();
                                $todayDriver = $todayAssignment?->driver ?? $v->driver;
                            @endphp
                            <tr>
                                <td>{{ $v->name }}</td>
                                <td>{{ $v->number_plate }}</td>
                                <td>{{ $v->type ?? '—' }}</td>
                                <td>
                                    @if($todayDriver)
                                        <strong>{{ $todayDriver->name }}</strong>
                                        @if($todayAssignment)
                                            <span class="badge bg-info text-dark ms-1">Scheduled</span>
                                        @else
                                            <span class="badge bg-secondary ms-1">Default</span>
                                        @endif
                                    @else
                                        <span class="text-danger">Not assigned</span>
                                    @endif
                                </td>
                                <td>{{ $v->driver?->name ?? '—' }}</td>
                                <td><span class="badge bg-{{ $v->status === 'active' ? 'success' : 'secondary' }}">{{ $v->status }}</span></td>
                                <td class="text-nowrap">
                                    @canRoute('vehicles.edit')
<a href="{{ route('vehicles.edit', $v) }}#daily-assignments" class="btn btn-sm btn-success">Assign driver</a>
@endcanRoute
                                    @canRoute('vehicles.edit')
<a href="{{ route('vehicles.edit', $v) }}" class="btn btn-sm btn-outline-primary">Edit</a>
@endcanRoute
                                    @canRoute('vehicles.destroy')
<form action="{{ route('vehicles.destroy', $v) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this vehicle?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
@endcanRoute
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center">No vehicles. <a href="{{ route('vehicles.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
