@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-people-fill me-2 text-primary"></i>All Users</h4>
    <a href="{{ route('erp.users.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Add User
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Permissions</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $i => $user)
                    <tr>
                        <td class="text-muted small">{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $user->name }}</strong>
                            @if($user->id === Auth::id())
                                <span class="badge bg-info ms-1">You</span>
                            @endif
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->roleModel)
                                <span class="badge bg-primary">{{ $user->roleModel->label }}</span>
                            @elseif($user->role === 'admin')
                                <span class="badge bg-danger">System Admin</span>
                            @else
                                <span class="badge bg-secondary">{{ $user->role }}</span>
                            @endif
                        </td>
                        <td>
                            @if($user->isSystemAdmin())
                                <span class="text-success small"><i class="bi bi-shield-fill-check me-1"></i>Full access</span>
                            @elseif($user->roleModel)
                                <span class="text-muted small">{{ count($user->roleModel->permissions ?? []) }} permissions</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $user->created_at->format('d M Y') }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                @if(!$user->isSystemAdmin() || auth()->user()->isSystemAdmin())
                                @canRoute('erp.users.edit')
<a href="{{ route('erp.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
@endcanRoute
                                @if($user->id !== Auth::id())
                                @canRoute('erp.users.destroy')
<form method="POST" action="{{ route('erp.users.destroy', $user) }}"
                                      onsubmit="return confirm('Delete user {{ addslashes($user->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
@endcanRoute
                                @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
