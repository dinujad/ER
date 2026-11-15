@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add New Unit</h1>
        <a href="{{ route('units.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('units.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Unit Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Kilogram" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Short Name <span class="text-danger">*</span></label>
                    <input type="text" name="short_name" class="form-control" placeholder="e.g. Kg" required>
                </div>

                <button type="submit" class="btn btn-primary">Save Unit</button>
            </form>
        </div>
    </div>
</div>
@endsection
