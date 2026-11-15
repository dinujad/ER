@extends('driver.layout')

@section('title', 'My Jobs - ' . config('app.name') . ' Driver')

@section('content')
<h1 class="h4 mb-4">My Jobs</h1>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Vehicle</th>
                    <th>Route</th>
                    <th>Total cost</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($runs as $r)
                <tr>
                    <td>{{ $r->run_date->format('d M Y') }}</td>
                    <td>{{ $r->vehicle?->number_plate }}</td>
                    <td>{{ $r->start_route ? $r->start_route . ' → ' . $r->end_route : '—' }}</td>
                    <td>Rs {{ number_format($r->items->sum('cost'), 2) }}</td>
                    <td><span class="badge bg-{{ $r->status === 'ended' ? 'success' : 'warning' }}">{{ $r->status }}</span></td>
                    <td class="text-nowrap">
                        @if($r->status === 'started')
                            <a href="{{ route('driver.dashboard') }}#expenses" class="btn btn-sm btn-success">Add expense</a>
                        @endif
                        <a href="{{ route('driver.jobs.show', $r) }}" class="btn btn-sm btn-outline-primary">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center">No jobs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $runs->links() }}</div>
@endsection
