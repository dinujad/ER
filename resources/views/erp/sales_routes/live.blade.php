@extends('erp.layout')

@section('title', 'Started Routes')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Started routes</h1>
            <p class="text-muted small mb-0">Sales persons currently on a route — start time and expenses so far.</p>
        </div>
        <a href="{{ route('sales-routes.runs') }}" class="btn btn-outline-secondary btn-sm">All routes</a>
    </div>

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
                        <th>Duration</th>
                        <th class="text-end">Petty cash</th>
                        <th class="text-end">Collected</th>
                        <th class="text-end">Expenses</th>
                        <th class="text-end">Handover</th>
                        <th>Add petty cash</th>
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
                            <td>{{ $run->durationLabel() }}</td>
                            @php $cash = $settlements[$run->id] ?? null; @endphp
                            <td class="text-end">Rs {{ number_format((float) ($cash->petty ?? 0), 2) }}</td>
                            <td class="text-end">Rs {{ number_format((float) ($cash->collected ?? 0), 2) }}</td>
                            <td class="text-end">
                                Rs {{ number_format((float) ($cash->expenses ?? $run->expenses_total ?? 0), 2) }}
                            </td>
                            <td class="text-end fw-semibold">Rs {{ number_format((float) ($cash->handover ?? 0), 2) }}</td>
                            <td>
                                <form method="POST" action="{{ route('sales-routes.runs.petty-cash', $run) }}" class="d-flex gap-1">
                                    @csrf
                                    <input type="number" name="amount" step="0.01" min="0.01" class="form-control form-control-sm" placeholder="Amount" required style="width:110px">
                                    <input type="text" name="note" class="form-control form-control-sm" placeholder="Note" maxlength="255" style="width:120px">
                                    <button class="btn btn-sm btn-primary">Add</button>
                                    <a href="{{ route('sales-routes.runs.show', $run) }}" class="btn btn-sm btn-outline-secondary">Report</a>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">No sales person has started a route right now.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
