@extends('erp.layout')

@section('title', 'Route Run Detail')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $run->salesPerson?->name }} — {{ $run->salesRoute?->name }}</h1>
            <p class="text-muted small mb-0">
                Started {{ $run->startedLabel() }}
                @if($run->completed_at)
                    · Completed {{ $run->completed_at->format('d M Y, h:i A') }}
                @endif
                · {{ $run->durationLabel() }}
            </p>
        </div>
        <a href="{{ route('sales-routes.runs') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Status</div>
                    @if($run->isActive())
                        <span class="badge bg-success">Active / started</span>
                    @else
                        <span class="badge bg-secondary">Completed</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Driver</div>
                    <div class="fw-semibold">{{ $run->driver_name ?: '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Assistants</div>
                    <div class="fw-semibold">{{ $run->assistants ?: '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Petty cash</div>
                    <div class="fs-4 fw-bold">Rs {{ number_format($settlement->petty, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Cash collected</div>
                    <div class="fs-4 fw-bold">Rs {{ number_format($settlement->collected, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Expenses</div>
                    <div class="fs-4 fw-bold">Rs {{ number_format($settlement->expenses, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Handover to office</div>
                    <div class="fs-4 fw-bold {{ $settlement->handover < 0 ? 'text-danger' : 'text-success' }}">Rs {{ number_format($settlement->handover, 2) }}</div>
                    <div class="small text-muted">Petty cash + collected cash − expenses</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white fw-semibold">Add petty cash</div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            <form method="POST" action="{{ route('sales-routes.runs.petty-cash', $run) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small">Amount (Rs)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label small">Note (optional)</label>
                    <input type="text" name="note" class="form-control" maxlength="255">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary">Add petty cash</button>
                </div>
            </form>
            @if($settlement->entries->isNotEmpty())
                <ul class="small mt-3 mb-0">
                    @foreach($settlement->entries as $entry)
                        <li>{{ $entry->created_at?->format('Y-m-d H:i') }} — Rs {{ number_format((float) $entry->amount, 2) }} @if($entry->note) · {{ $entry->note }} @endif</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white fw-semibold">Cash collected by business</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Business name</th>
                        <th>Orders</th>
                        <th class="text-end">Cash</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($settlement->collections as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->business }}</td>
                            <td class="small">{{ $row->orders->implode(', ') ?: '—' }}</td>
                            <td class="text-end">Rs {{ number_format($row->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No cash collected on this route yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="text-muted small">Route</div>
            <div class="fw-semibold">{{ $run->salesRoute?->name }}</div>
            @if($run->salesRoute?->code)
                <div class="small text-muted">{{ $run->salesRoute->code }}</div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-semibold">Expenses on this trip</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th class="text-end">Amount</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($run->expenses as $ex)
                        <tr>
                            <td>{{ optional($ex->expense_date)->format('d M Y') ?? '—' }}</td>
                            <td>{{ $ex->typeLabel() }}</td>
                            <td>
                                {{ $ex->description }}
                                @if($ex->notes)
                                    <div class="small text-muted">{{ $ex->notes }}</div>
                                @endif
                            </td>
                            <td class="text-end">Rs {{ number_format((float) $ex->amount, 2) }}</td>
                            <td>
                                @include('erp.sales_routes.partials.expense-receipt', ['expense' => $ex])
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No expenses recorded on this trip yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
