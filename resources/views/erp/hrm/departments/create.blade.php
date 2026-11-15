@extends('erp.layout')

@section('content')
<div class="mb-4"><a href="{{ route('departments.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Departments</a></div>
<h4 class="mb-3">Add Department</h4>
<div class="card border-0 shadow-sm" style="max-width: 400px;">
    <div class="card-body">
        <form method="POST" action="{{ route('departments.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="e.g. HR">
                @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
