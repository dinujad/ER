@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Employee Salaries</h4>
    <a href="{{ route('employee-salaries.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Set Salary</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Employee</th><th>Department</th><th>Designation</th><th>Basic salary</th><th>Effective from</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($employees as $emp)
                @php $s = $salaries[$emp->id] ?? null; @endphp
                <tr>
                    <td>{{ $emp->name }}</td>
                    <td>{{ $emp->department->name ?? '—' }}</td>
                    <td>{{ $emp->designation->title ?? '—' }}</td>
                    <td>{{ $s ? number_format($s->basic_salary, 2) : '—' }}</td>
                    <td>{{ $s ? $s->effective_from->format('M d, Y') : '—' }}</td>
                    <td><a href="{{ route('employee-salaries.create', ['employee_id' => $emp->id]) }}" class="btn btn-sm btn-outline-primary">Set / Update</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
