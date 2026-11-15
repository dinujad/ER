@extends('erp.layout')

@section('content')
<div class="mb-4"><a href="{{ route('designations.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Designations</a></div>
<h4 class="mb-3">Edit Designation</h4>
<div class="card border-0 shadow-sm" style="max-width: 450px;">
    <div class="card-body">
        @canRoute('designations.update')
<form method="POST" action="{{ route('designations.update', $designation) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $designation->title) }}" required>
                @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Department <span class="text-danger">*</span></label>
                <select name="department_id" class="form-select" required>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ old('department_id', $designation->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Level</label>
                <input type="number" name="level" class="form-control" value="{{ old('level', $designation->level) }}" min="1">
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('designations.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
@endcanRoute
    </div>
</div>
@endsection
