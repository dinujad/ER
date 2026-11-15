@extends('erp.layout')

@section('title', 'Paid Sales Commissions')

@section('content')
<div class="container-fluid">
    <h3 class="mb-4">Paid Sales Commissions</h3>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('commissions.history') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Sales Person</label>
                    <select name="sales_person_id" class="form-select">
                        <option value="">-- All Sales Persons --</option>
                        @foreach($salesPersons as $sp)
                            <option value="{{ $sp->id }}" {{ request('sales_person_id') == $sp->id ? 'selected' : '' }}>
                                {{ $sp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Commission History</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Sales Person</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                            <th>Paid Date</th>
                            <th class="text-end">Total Commission</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commissions as $commission)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($commission->month)->format('F Y') }}</td>
                            <td>{{ $commission->user?->name ?? '—' }}</td>
                            <td>
                                @if($commission->status == 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td>{{ $commission->status == 'paid' ? ucwords(str_replace('_', ' ', $commission->payment_method)) : '-' }}</td>
                            <td>{{ $commission->paid_at ? \Carbon\Carbon::parse($commission->paid_at)->format('d.m.Y') : '-' }}</td>
                            <td class="text-end fw-bold">{{ number_format($commission->total_amount, 2) }}</td>
                            <td class="text-center">
                                <a href="{{ route('commissions.index', ['sales_person_id' => $commission->user_id, 'month' => $commission->month]) }}" class="btn btn-sm btn-info" title="View Details">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="{{ route('commissions.export', ['sales_person_id' => $commission->user_id, 'month' => $commission->month]) }}" class="btn btn-sm btn-secondary" title="Export PDF" target="_blank">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No commission records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
