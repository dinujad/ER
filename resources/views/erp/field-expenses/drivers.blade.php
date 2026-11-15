@extends('erp.layout')

@section('title', 'Driver Expenses')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-truck me-2 text-primary"></i>Driver Expenses</h1>
            <p class="text-muted small mb-0">Fuel, toll & other costs from vehicle runs — view receipts / slips.</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-primary-subtle text-primary border px-3 py-2">Total: <strong>Rs {{ number_format($totalAmount, 2) }}</strong></span>
            <span class="badge bg-success-subtle text-success border px-3 py-2">With receipt: <strong>{{ $withSlipCount }}</strong></span>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Month</label>
                    <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($monthOptions as $opt)
                            <option value="{{ $opt['value'] }}" @selected($month === $opt['value'])>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Driver</label>
                    <select name="driver_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" @selected((string) request('driver_id') === (string) $driver->id)>{{ $driver->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="fuel" @selected(request('type') === 'fuel')>Fuel</option>
                        <option value="toll" @selected(request('type') === 'toll')>Toll</option>
                        <option value="others" @selected(request('type') === 'others')>Others</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Receipt</label>
                    <select name="has_slip" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="yes" @selected(request('has_slip') === 'yes')>With receipt</option>
                        <option value="no" @selected(request('has_slip') === 'no')>No receipt</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    <a href="{{ route('field-expenses.drivers.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Driver</th>
                        <th>Vehicle</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="text-nowrap">{{ $item->run?->run_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $item->run?->driver?->name ?? '—' }}</td>
                            <td class="small">{{ $item->run?->vehicle?->number_plate ?? '—' }}</td>
                            <td>{{ ucfirst($item->type) }}</td>
                            <td>{{ $item->name }}</td>
                            <td class="text-end font-monospace">{{ number_format($item->cost, 2) }}</td>
                            <td>@include('partials.expense-receipt-link', ['item' => $item])</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">No driver expenses for {{ $start->format('F Y') }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="card-footer bg-white border-top-0">{{ $items->links() }}</div>
        @endif
    </div>
</div>
@endsection
