@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-shield-plus me-2 text-primary"></i>Add Role</h4>
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

                @canRoute('roles.store')
<form method="POST" action="{{ route('roles.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role Name / Label <span class="text-danger">*</span></label>
                        <input type="text" name="label" class="form-control" value="{{ old('label') }}"
                               placeholder="e.g. Accountant, Warehouse Manager" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}"
                               placeholder="Brief description of this role (optional)">
                    </div>

                    @php $selectedPerms = old('permissions', []); @endphp
                    @include('erp.roles._permission_form')

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Create Role</button>
                        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
@endcanRoute
            </div>
        </div>
    </div>
</div>
@endsection
