@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Import Attendance (Excel/CSV)</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> <strong> Instructions:</strong><br>
                        Please upload a <strong>CSV</strong> file with the following column order (without headers):<br>
                        <code>Employee No, Date (YYYY-MM-DD), Time (HH:mm:ss)</code>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('attendance.import.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="file" class="form-label">Select File</label>
                            <input type="file" class="form-control" id="file" name="file" required accept=".csv, .txt">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('attendance.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success"><i class="bi bi-upload me-2"></i> Upload & Process</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
