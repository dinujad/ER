@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-shield-lock me-2 text-primary"></i>Roles & Permissions</h4>
    @canRoute('roles.create')
<a href="{{ route('roles.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Add Role
    </a>
@endcanRoute
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3">
    @foreach($roles as $role)
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 {{ $role->isAdmin() ? 'border-danger border-opacity-50' : '' }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong>{{ $role->label }}</strong>
                    @if($role->isAdmin())
                        <span class="badge bg-danger ms-1">System Admin</span>
                    @endif
                    <div class="text-muted small">{{ $role->users_count }} user(s)</div>
                </div>
                <div class="d-flex gap-1">
                    @canRoute('roles.edit')
<a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil"></i>
                    </a>
@endcanRoute
                    @if(!$role->isAdmin() && $role->users_count === 0)
                    @canRoute('roles.destroy')
<form method="POST" action="{{ route('roles.destroy', $role) }}"
                          onsubmit="return confirm('Delete role {{ addslashes($role->label) }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
@endcanRoute
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($role->description)
                    <p class="text-muted small mb-2">{{ $role->description }}</p>
                @endif

                @if($role->isAdmin())
                    <p class="text-success small mb-0"><i class="bi bi-shield-fill-check me-1"></i>Full access to everything</p>
                @else
                    @php $perms = $role->permissions ?? []; @endphp
                    @if(empty($perms))
                        <p class="text-muted small mb-0">No permissions assigned yet.</p>
                    @else
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($permissionGroups as $groupKey => $group)
                                @php
                                    $groupPerms = array_keys($group['permissions']);
                                    $assigned = array_intersect($groupPerms, $perms);
                                @endphp
                                @if(count($assigned) > 0)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 py-1 px-2">
                                        {{ $group['label'] }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                        <p class="text-muted small mt-2 mb-0">{{ count($perms) }} permission(s)</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
