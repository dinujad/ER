@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Drivers</h1>
        <a href="{{ route('drivers.create') }}" class="btn btn-primary">Add Driver</a>
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="card shadow mb-4">
        <div class="card-body">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>License No</th>
                        <th>Login (Email)</th>
                        <th>Today's vehicle</th>
                        <th>Default vehicle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drivers as $d)
                    <tr>
                        <td>{{ $d->name }}</td>
                        <td>{{ $d->phone ?? '—' }}</td>
                        <td>{{ $d->license_no ?? '—' }}</td>
                        <td>{{ $d->user?->email ?? '—' }}</td>
                        @php
                            $todayAssignment = $d->vehicleAssignments->first();
                            $todayVehicle = $todayAssignment?->vehicle ?? $d->vehicle;
                        @endphp
                        <td>
                            @if($todayVehicle)
                                {{ $todayVehicle->number_plate }}
                                @if($todayAssignment)
                                    <span class="badge bg-info text-dark">Scheduled</span>
                                @else
                                    <span class="badge bg-secondary">Default</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $d->vehicle?->number_plate ?? '—' }}</td>
                        <td>
                            @canRoute('drivers.edit')
<a href="{{ route('drivers.edit', $d) }}" class="btn btn-sm btn-outline-primary">Edit</a>
@endcanRoute
                            @canRoute('drivers.destroy')
<form action="{{ route('drivers.destroy', $d) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this driver?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
@endcanRoute
                        </td>
                    </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No drivers.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
