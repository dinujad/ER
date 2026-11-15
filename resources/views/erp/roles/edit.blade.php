@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-shield-lock me-2 text-primary"></i>Edit Role — {{ $role->label }}</h4>
    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                @canRoute('roles.update')
<form method="POST" action="{{ route('roles.update', $role) }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role Name / Label <span class="text-danger">*</span></label>
                        <input type="text" name="label" class="form-control"
                               value="{{ old('label', $role->label) }}" required
                               {{ $role->isAdmin() ? 'readonly' : '' }}>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control"
                               value="{{ old('description', $role->description) }}"
                               placeholder="Brief description (optional)">
                    </div>

                    @php $selectedPerms = session()->hasOldInput('label') ? old('permissions', []) : ($role->permissions ?? []); @endphp
                    @include('erp.roles._permission_form')

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
                        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
@endcanRoute
            </div>
        </div>
    </div>
</div>
@endsection
