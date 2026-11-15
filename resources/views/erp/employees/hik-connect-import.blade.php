@extends('erp.layout')

@section('title', 'Import Hik-Connect Employees')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Import Hik-Connect Employees</h1>
            <p class="text-muted mb-0">Upload the Hik-Connect <strong>Person Information</strong> workbook. People are imported as ERP employees; the Excel <code>ID</code> is stored as their Hik-Connect person ID for later attendance matching.</p>
        </div>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <ul class="small text-muted mb-3">
                <li>Use the official Hik-Connect export (<code>.xlsx</code>). Instruction rows at the top are skipped automatically.</li>
                <li>Fingerprint, PIN, card and access-permission columns are ignored and never stored.</li>
                <li>This file is people only — no attendance punches are created.</li>
                <li>Re-importing the same IDs updates the mapped employees; it will not create duplicates.</li>
            </ul>
            <form action="{{ route('employees.hik-connect-import.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="file" class="form-label">Hik-Connect Person Information (.xlsx)</label>
                    <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls" required>
                    @error('file')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-eye me-1"></i> Preview import</button>
            </form>
        </div>
    </div>
</div>
@endsection
