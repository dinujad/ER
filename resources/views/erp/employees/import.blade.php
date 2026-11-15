@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Import Employees</h1>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i> Back to List</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Import from CSV or Excel</h6>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                Upload a <strong>CSV</strong> file, or export your list from <strong>Excel</strong> as CSV (File → Save As → CSV UTF-8) and upload here.
            </p>
            <p class="small text-muted mb-3">
                Expected columns: <strong>ID</strong>, <strong>First Name</strong>, <strong>Last Name</strong>, <strong>Department</strong>, <strong>Time of Effective Period (Start Time)</strong>, <strong>Time of Effective Period (End Time)</strong>, <strong>Self-Service</strong> (Enable/Disable), <strong>Email</strong>.
            </p>
            <form action="{{ route('employees.import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="default_role" class="form-label">Default role for imported employees</label>
                    <select name="default_role" id="default_role" class="form-select">
                        @foreach($roles ?? [] as $role)
                            <option value="{{ $role->name }}" {{ $role->name === 'employee' ? 'selected' : '' }}>{{ $role->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="file" class="form-label">CSV File <span class="text-muted">(or Excel saved as CSV)</span></label>
                    <input type="file" name="file" id="file" class="form-control" accept=".csv,.txt,.xlsx,.xls" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Import</button>
            </form>
        </div>
    </div>
</div>
@endsection
