@extends('erp.layout')

@section('title', 'All Route Runs')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">All routes</h1>
            <p class="text-muted small mb-0">Old routes too. Petty cash, cash collected, expenses, and the amount to hand over to the office.</p>
        </div>
        <a href="{{ route('sales-routes.live') }}" class="btn btn-outline-success btn-sm">Started now</a>
    </div>

    <form method="GET" class="card shadow-sm border-0 mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="all" @selected($status === 'all')>All</option>
                    <option value="active" @selected($status === 'active')>Active (started)</option>
                    <option value="completed" @selected($status === 'completed')>Completed</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Sales person</label>
                <select name="sales_person_id" class="form-select form-select-sm">
                    <option value="">All sales persons</option>
                    @foreach($salesPersons as $sp)
                        <option value="{{ $sp->id }}" @selected((string) $salesPersonId === (string) $sp->id)>{{ $sp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary btn-sm" type="submit">Filter</button>
                <a href="{{ route('sales-routes.runs') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </div>
    </form>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Sales person</th>
                        <th>Route</th>
                        <th>Driver</th>
                        <th>Assistants</th>
                        <th>Started</th>
                        <th>Completed</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th class="text-end">Petty cash</th>
                        <th class="text-end">Collected</th>
                        <th class="text-end">Expenses</th>
                        <th class="text-end">Handover</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td class="fw-semibold">{{ $run->salesPerson?->name ?? '—' }}</td>
                            <td>{{ $run->salesRoute?->name ?? '—' }}</td>
                            <td>{{ $run->driver_name ?: '—' }}</td>
                            <td>{{ $run->assistants ?: '—' }}</td>
                            <td>{{ $run->startedLabel() }}</td>
                            <td>{{ $run->completed_at?->format('d M Y, h:i A') ?? '—' }}</td>
                            <td>{{ $run->durationLabel() }}</td>
                            <td>
                                @if($run->isActive())
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Completed</span>
                                @endif
                            </td>
                            @php $cash = $settlements[$run->id] ?? null; @endphp
                            <td class="text-end">{{ number_format((float) ($cash->petty ?? 0), 2) }}</td>
                            <td class="text-end">{{ number_format((float) ($cash->collected ?? 0), 2) }}</td>
                            <td class="text-end">{{ number_format((float) ($cash->expenses ?? 0), 2) }}</td>
                            <td class="text-end fw-semibold {{ ($cash->handover ?? 0) < 0 ? 'text-danger' : '' }}">{{ number_format((float) ($cash->handover ?? 0), 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('sales-routes.runs.show', $run) }}" class="btn btn-sm btn-outline-primary">Report</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="13" class="text-center text-muted py-4">No route runs match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($runs->hasPages())
            <div class="card-footer">{{ $runs->links() }}</div>
        @endif
    </div>
</div>
@endsection
