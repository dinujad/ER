@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">Edit Expense Category</h4>
        <a href="{{ route('expense-categories.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            @canRoute('expense-categories.update')
<form action="{{ route('expense-categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $category->code) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $category->description) }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
@endcanRoute
        </div>
    </div>
</div>
@endsection
