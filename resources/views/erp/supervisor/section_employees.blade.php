@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('supervisor.dashboard') }}" class="text-decoration-none small text-secondary"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
            <h4 class="mb-0 mt-1">{{ $section->name }} — Section employees</h4>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>NIC / Contact</th>
                            <th>Has login</th>
                            <th width="120">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $emp)
                        <tr>
                            <td>
                                @if($emp->id === $employee->id)
                                    <span class="badge bg-warning text-dark me-1">You</span>
                                @endif
                                {{ $emp->name }}
                            </td>
                            <td>{{ $emp->nic ?? '—' }} / {{ $emp->contact ?? '—' }}</td>
                            <td>{{ $emp->user_id ? 'Yes' : 'No' }}</td>
                            <td>
                                @if($emp->user_id)
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#passwordModal-{{ $emp->id }}">
                                        Edit password
                                    </button>
                                @else
                                    <span class="text-muted small">No account</span>
                                @endif
                            </td>
                        </tr>
                        {{-- Password modal per employee --}}
                        @if($emp->user_id)
                        <div class="modal fade" id="passwordModal-{{ $emp->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    @canRoute('supervisor.employees.password')
<form method="POST" action="{{ route('supervisor.employees.password', $emp) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Set password — {{ $emp->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">New password</label>
                                                <input type="password" name="password" class="form-control" required>
                                                @error('password')<span class="text-danger small">{{ $message }}</span>@enderror
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Confirm password</label>
                                                <input type="password" name="password_confirmation" class="form-control" required>
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
