@extends('erp.layout')

@section('title', 'Washing Stock')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1">Washing Stock</h1>
            <p class="text-muted small mb-0">Line production goes here first (job-wise). From a job card you create the same washing gate pass as before.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('manufacturing.washing') }}" class="btn btn-sm btn-outline-primary">Washing / Gate Pass</a>
            <a href="{{ route('hourly-production.create') }}" class="btn btn-sm btn-outline-secondary">Line Production</a>
        </div>
    </div>

    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Search job / style / product</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="J-104, Colombo…">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary btn-sm" type="submit">Search</button>
                    <a href="{{ route('manufacturing.washing-stock.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Job</th>
                        <th>Product</th>
                        <th class="text-end">Line produced</th>
                        <th class="text-end">In washing stock</th>
                        <th class="text-end">Sent to washing</th>
                        <th class="text-end">On hand</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                        @php
                            $produced = $job->totalLineHourlyQuantity();
                            $received = $job->washingStockReceived();
                            $sent = $job->totalWashingSentQuantity();
                            $onHand = $job->washingStockOnHand();
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('manufacturing.washing-stock.show', $job) }}" class="fw-semibold text-decoration-none">{{ $job->job_number }}</a>
                                @if($job->style_ref)
                                    <div class="small text-muted">{{ $job->style_ref }}</div>
                                @endif
                            </td>
                            <td>{{ $job->product?->name ?? '—' }}</td>
                            <td class="text-end font-monospace">{{ number_format($produced) }}</td>
                            <td class="text-end font-monospace">{{ number_format($received) }}</td>
                            <td class="text-end font-monospace">{{ number_format($sent) }}</td>
                            <td class="text-end">
                                <strong class="{{ $onHand > 0 ? 'text-success' : 'text-muted' }}">{{ number_format($onHand) }}</strong>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('manufacturing.washing-stock.show', $job) }}" class="btn btn-sm btn-outline-primary">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No washing stock yet. Send pieces from Line Production first.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($jobs->hasPages())
            <div class="card-footer">{{ $jobs->links() }}</div>
        @endif
    </div>
</div>
@endsection
