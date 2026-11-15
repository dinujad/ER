@extends('erp.layout')

@section('content')
<h4 class="mb-3">HR reports</h4>
<div class="row g-3 mb-3">
    @foreach(['Headcount' => $headcount, 'Present today' => $present, 'Absent today' => $absent, 'Late today' => $late, 'Early leave' => $early, 'Missing checkout' => $missing, 'OT hours this month' => number_format($otHours, 1), 'OT cost' => 'Rs '.number_format($otCost, 2), 'Incentives' => 'Rs '.number_format($incentives, 2), 'EPF this month' => 'Rs '.number_format($epf, 2), 'ETF this month' => 'Rs '.number_format($etf, 2), 'Open vacancies' => $vacancies, 'Training this month' => $training, 'Documents expiring' => $expiring, 'Turnover this year' => $turnover] as $label => $value)
        <div class="col-6 col-md-4 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="fs-4">{{ $value }}</div></div></div></div>
    @endforeach
</div>
<div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-primary" href="{{ route('hr.reports.export', 'headcount') }}">Employee register CSV</a>
    <a class="btn btn-outline-primary" href="{{ route('hr.reports.export', 'attendance') }}">Today attendance CSV</a>
    <a class="btn btn-outline-primary" href="{{ route('hr.reports.export', 'cost') }}">OT and incentive CSV</a>
    <a class="btn btn-outline-secondary" href="{{ route('leave-requests.balances') }}">Leave balances</a>
    <a class="btn btn-outline-secondary" href="{{ route('payroll-runs.index') }}">Payroll</a>
    <a class="btn btn-outline-secondary" href="{{ route('hr.manpower.index') }}">Manpower</a>
</div>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header"><h6 class="mb-0">Labour cost by department</h6></div>
    <table class="table mb-0">
        <thead class="table-light"><tr><th>Department</th><th class="text-end">Net paid</th></tr></thead>
        <tbody>
            @forelse($labour as $row)
                <tr><td>{{ $row->department }}</td><td class="text-end">Rs {{ number_format($row->cost, 2) }}</td></tr>
            @empty
                <tr><td colspan="2" class="text-muted">No payroll items yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
