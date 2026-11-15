@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-white">Supervisors</h6>
                    <a href="{{ route('employees.index') }}" class="btn btn-light btn-sm">Employee List</a>
                </div>
                <div class="card-body px-0 pb-2">
                    <p class="text-muted small px-4 mb-3">Section supervisors can change their own password from their dashboard. Here you can set or reset their login password.</p>
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Name</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Section(s)</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Email</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Login</th>
                                    <th class="text-secondary opacity-7">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($supervisors as $row)
                                @php $emp = $row['employee']; @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $emp->name }}</h6>
                                                @if($emp->nic)<p class="text-xs text-secondary mb-0">{{ $emp->nic }}</p>@endif
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="text-sm">{{ $row['sections'] ?: '—' }}</span></td>
                                    <td><span class="text-sm">{{ $emp->email ?? '—' }}</span></td>
                                    <td class="text-center">
                                        @if($row['has_login'])
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No account</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($emp->email)
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#passwordModal-{{ $emp->id }}">
                                                <i class="bi bi-key me-1"></i> Set / Change password
                                            </button>
                                        @else
                                            <span class="text-muted small">Add email in Employee first</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No section supervisors assigned. Assign supervisors from Production Sections → Manage team.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($supervisors as $row)
@php $emp = $row['employee']; @endphp
@if($emp->email)
<div class="modal fade" id="passwordModal-{{ $emp->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            @canRoute('supervisors.password.update')
<form action="{{ route('supervisors.password.update', $emp) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Set password — {{ $emp->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">They will use this password to log in to their supervisor dashboard. They can change it later from their dashboard.</p>
                    <div class="mb-3">
                        <label class="form-label">New password</label>
                        <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                        @error('password')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm password</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update password</button>
                </div>
            </form>
@endcanRoute
        </div>
    </div>
</div>
@endif
@endforeach
@endsection
