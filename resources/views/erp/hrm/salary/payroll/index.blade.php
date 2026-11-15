@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h4 class="mb-0">Payroll</h4>
    <a href="{{ route('payroll-runs.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add</a>
</div>

@if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3 border-0">
    <li class="nav-item">
        <a class="nav-link {{ ($tab ?? 'payrolls') === 'payrolls' ? 'active fw-semibold' : 'text-dark' }}" href="{{ route('payroll-runs.index', ['tab' => 'payrolls'] + request()->only('per_page', 'month', 'year', 'status', 'search')) }}">All Payrolls</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($tab ?? '') === 'groups' ? 'active fw-semibold' : 'text-dark' }}" href="{{ route('payroll-runs.index', ['tab' => 'groups'] + request()->only('per_page')) }}">All payroll groups</a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-dark" href="{{ route('salary-components.index') }}"><i class="bi bi-eye me-1"></i> Pay Components</a>
    </li>
</ul>

@if(($tab ?? 'payrolls') === 'payrolls')
    {{-- Filters & toolbar --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('payroll-runs.index') }}" class="row g-2 align-items-center flex-wrap">
                <input type="hidden" name="tab" value="payrolls">
                <div class="col-auto">
                    <label class="col-form-label col-form-label-sm">Show</label>
                </div>
                <div class="col-auto">
                    <select name="per_page" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        @foreach([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>{{ $n }} entries</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="month" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <option value="">All months</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ request('month') == (string)$m ? 'selected' : '' }}>{{ now()->month($m)->format('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-auto">
                    <select name="year" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <option value="">All years</option>
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" {{ request('year') == (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="due" {{ request('status') === 'due' ? 'selected' : '' }}>Due</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div class="col-auto ms-md-auto d-flex flex-wrap gap-2">
                    <a href="{{ route('payroll-runs.index', array_merge(request()->all(), ['tab' => 'payrolls', 'export' => 'csv'])) }}" class="btn btn-sm btn-outline-secondary">Export CSV</a>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print</button>
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Month/Year</th>
                            <th>Reference No</th>
                            <th class="text-end">Total amount</th>
                            <th>Payment Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items ?? [] as $item)
                        <tr>
                            <td>{{ $item->employee?->name ?? '—' }}</td>
                            <td>{{ $item->employee?->department?->name ?? '—' }}</td>
                            <td>{{ $item->employee?->designation?->title ?? '—' }}</td>
                            <td>{{ $item->payrollRun ? $item->payrollRun->period_start->format('F Y') : '—' }}</td>
                            <td>{{ $item->reference_no ?? ($item->payrollRun ? $item->payrollRun->period_start->format('Y') . '/' . str_pad((string)$item->id, 4, '0', STR_PAD_LEFT) : '—') }}</td>
                            <td class="text-end fw-semibold">Rs {{ number_format($item->net_salary, 2) }}</td>
                            <td>
                                @if(($item->payment_status ?? 'due') === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif(($item->payment_status ?? '') === 'partial')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @else
                                    <span class="badge bg-warning text-dark">Due</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Actions</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('payroll-runs.show', $item->payrollRun) }}">View run</a></li>
                                        @if($item->payrollRun && $item->payrollRun->status === 'draft')
                                            <li>@canRoute('payroll-runs.items.edit')
<a class="dropdown-item" href="{{ route('payroll-runs.items.edit', [$item->payrollRun, $item]) }}">Edit</a>
@endcanRoute
</li>
                                        @endif
                                        <li><a class="dropdown-item" href="{{ route('payroll-runs.payslip', [$item->payrollRun, $item]) }}" target="_blank"><i class="bi bi-printer me-1"></i> Print paysheet</a></li>
                                        @if(($item->payment_status ?? 'due') !== 'paid')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('payroll-runs.items.mark-paid', $item) }}" method="POST" class="dropdown-item p-0">
                                                    @csrf
                                                    <button type="submit" class="btn btn-link dropdown-item text-success p-2">Mark as paid</button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No payroll entries. <a href="{{ route('payroll-runs.create') }}">Add payroll</a> to get started.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(isset($items) && $items->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap">
            <small class="text-muted">Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} entries</small>
            {{ $items->links() }}
        </div>
        @endif
    </div>
@else
    {{-- All payroll groups --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Period</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Status</th>
                        <th>Created by</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($runs ?? [] as $run)
                    <tr>
                        <td><strong>{{ $run->title }}</strong></td>
                        <td>{{ $run->period_start->format('M d, Y') }}</td>
                        <td>{{ $run->period_end->format('M d, Y') }}</td>
                        <td><span class="badge {{ $run->status === 'finalized' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($run->status) }}</span></td>
                        <td>{{ $run->creator?->name ?? '—' }}</td>
                        <td class="text-end"><a href="{{ route('payroll-runs.show', $run) }}" class="btn btn-sm btn-outline-primary">View / Payslips</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No payroll groups. <a href="{{ route('payroll-runs.create') }}">Create one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($runs) && $runs->hasPages())
        <div class="card-footer bg-white">{{ $runs->links() }}</div>
        @endif
    </div>
@endif
@endsection
