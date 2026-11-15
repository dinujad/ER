@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Assign Employees to {{ $line->name }}</h5>
                        <a href="{{ route('lines.index') }}" class="btn btn-outline-secondary btn-sm">Back to List</a>
                    </div>
                    <p class="text-sm">Select employees to assign to this line. Unchecked employees will be removed from this line.</p>
                </div>
                <div class="card-body">
                    <form action="{{ route('lines.assign.store', $line->id) }}" method="POST">
                        @csrf
                        <div class="row">
                            @foreach($employees as $employee)
                            <div class="col-md-3 mb-3">
                                <div class="form-check card p-2 border h-100">
                                    <input class="form-check-input ms-1 mt-1" type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" id="emp{{ $employee->id }}"
                                        {{ $employee->production_line_id == $line->id ? 'checked' : '' }}>
                                    <label class="form-check-label ms-2 w-100 cursor-pointer" for="emp{{ $employee->id }}">
                                        <div class="d-flex flex-column">
                                            <span class="font-weight-bold text-dark">{{ $employee->name }}</span>
                                            <span class="text-xs text-muted">{{ ucfirst($employee->role) }}</span>
                                            @if($employee->production_line_id && $employee->production_line_id != $line->id)
                                                <span class="text-xs text-warning">(Currently pending transfer)</span>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Save Assignments</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
