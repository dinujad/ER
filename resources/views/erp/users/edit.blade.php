@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-person-gear me-2 text-primary"></i>Edit User — {{ $user->name }}</h4>
    <a href="{{ route('erp.users.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                @canRoute('erp.users.update')
<form method="POST" action="{{ route('erp.users.update', $user) }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>

                    @if(auth()->user()->isSystemAdmin())
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                        <select name="role_id" class="form-select" required>
                            <option value="">-- Select Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ (old('role_id', $user->role_id) == $role->id) ? 'selected' : '' }}>
                                    {{ $role->label }}
                                    @if($role->description) — {{ $role->description }} @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @else
                        <p class="text-muted small">Role: {{ $user->roleModel?->label ?? 'Unassigned' }}. Only administrators can change roles.</p>
                    @endif

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
                        <a href="{{ route('erp.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
@endcanRoute
            </div>
        </div>
    </div>
</div>
@endsection
