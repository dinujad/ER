@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Import Products</h1>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Upload CSV File</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Instructions:</strong>
                <ul class="mb-0">
                    <li>Download the template file to see the required format.</li>
                    <li>Ensure SKU is unique. Existing SKUs will be skipped.</li>
                    <li>Categories and Brands will be created automatically if they don't exist.</li>
                    <li>Units will default to "Pieces". Update manually if needed.</li>
                </ul>
                <div class="mt-3">
                    <a href="{{ route('products.import.template') }}" class="btn btn-sm btn-info">
                        <i class="bi bi-download"></i> Download CSV Template
                    </a>
                </div>
            </div>

            <form action="{{ route('products.import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Select CSV File</label>
                    <input type="file" name="csv_file" class="form-control" required accept=".csv, .txt">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-cloud-upload"></i> Upload & Import
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
