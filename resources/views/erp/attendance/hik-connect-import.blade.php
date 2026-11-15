@extends('erp.layout')

@section('title', 'Import Hik-Connect Attendance')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Import Hik-Connect Attendance</h1>
            <p class="text-muted mb-0">Upload a Hik-Connect <strong>Total Time Card</strong> workbook. Rows match existing employees by Hikvision person ID. New employees are not created.</p>
        </div>
        <a href="{{ route('attendance.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <ul class="small text-muted mb-3">
                <li>Report title rows are skipped. The header is found by <code>ID</code>, <code>Date</code>, <code>Clock-In Time</code> and <code>Clock-Out Time</code>.</li>
                <li>Durations such as <code>09:10</code> are hours and minutes, not clock times.</li>
                <li>No clock-in is saved as Absent. A clock-in without checkout stays incomplete.</li>
                <li>Manual corrections and finalized attendance are not overwritten.</li>
            </ul>
            <form action="{{ route('attendance.hik-connect-import.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="file" class="form-label">Total Time Card (.xlsx)</label>
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
